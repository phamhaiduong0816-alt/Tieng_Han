<?php
/**
 * Lớp hỗ trợ đọc và phân tích file CSV / Excel (.xlsx)
 * Korean Vocab Master - Giai đoạn 1
 */

require_once __DIR__ . '/mb_compat.php';

// Tự động nạp Composer autoloader nếu có
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

class FileParser {

    /**
     * Đọc nội dung file CSV hoặc XLSX và trả về mảng 2 chiều [headers, rows]
     * 
     * @param string $filePath Đường dẫn file tạm
     * @param string $originalName Tên file gốc để xác định đuôi file
     * @return array ['headers' => array, 'rows' => array, 'total_rows' => int]
     * @throws Exception
     */
    public static function parse(string $filePath, string $originalName): array {
        if (!file_exists($filePath)) {
            throw new Exception("File không tồn tại trên máy chủ.");
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return self::parseCsv($filePath);
        } elseif ($extension === 'xlsx') {
            return self::parseXlsx($filePath);
        } else {
            throw new Exception("Định dạng file không được hỗ trợ. Chỉ chấp nhận .csv và .xlsx");
        }
    }

    /**
     * Phân tích file CSV với hỗ trợ UTF-8, BOM và tự phát hiện dấu phân cách (, ; \t)
     */
    public static function parseCsv(string $filePath): array {
        $content = file_get_contents($filePath);
        if ($content === false || strlen($content) === 0) {
            throw new Exception("File CSV trống hoặc không thể đọc.");
        }

        // Xóa UTF-8 BOM nếu có
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Tự động kiểm tra và chuyển mã nếu file không phải UTF-8 (ví dụ Windows-1258, UTF-16, v.v.)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $detected = mb_detect_encoding($content, ['UTF-8', 'Windows-1258', 'CP949', 'EUC-KR', 'ISO-8859-1'], true);
            if ($detected) {
                $content = mb_convert_encoding($content, 'UTF-8', $detected);
            }
        }

        // Tự phát hiện dấu phân cách (dấu phẩy, chấm phẩy hoặc tab)
        $firstLine = strtok($content, "\r\n");
        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $cnt = substr_count($firstLine, $delim);
            if ($cnt > $maxCount) {
                $maxCount = $cnt;
                $bestDelimiter = $delim;
            }
        }

        // Đọc từng dòng CSV từ stream bộ nhớ
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $headers = [];
        $rows = [];
        $lineNum = 0;

        while (($data = fgetcsv($stream, 0, $bestDelimiter)) !== false) {
            // Bỏ qua dòng trống hoàn toàn
            $nonEmpty = array_filter($data, function($val) {
                return trim($val) !== '';
            });
            if (empty($nonEmpty)) {
                continue;
            }

            $lineNum++;
            if ($lineNum === 1) {
                // Hàng đầu tiên là tiêu đề cột
                $headers = array_map(function($h) {
                    return trim(preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/', '', (string)$h));
                }, $data);
            } else {
                // Điền đủ số phần tử theo số lượng header
                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$idx] = isset($data[$idx]) ? trim((string)$data[$idx]) : '';
                }
                $rows[] = $row;
            }
        }

        fclose($stream);

        if (empty($headers)) {
            throw new Exception("Không tìm thấy hàng tiêu đề hợp lệ trong file CSV.");
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => count($rows)
        ];
    }

    /**
     * Phân tích file Excel .xlsx
     * Kiểm tra PhpSpreadsheet trước, sau đó thử qua ZipArchive + XML nếu có
     */
    public static function parseXlsx(string $filePath): array {
        // Cách 1: Sử dụng PhpSpreadsheet nếu có thư viện
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rowsData = $worksheet->toArray(null, true, true, false);

                if (empty($rowsData)) {
                    throw new Exception("File Excel không có dữ liệu.");
                }

                $headers = array_map(function($h) {
                    return trim((string)$h);
                }, array_shift($rowsData));

                // Bỏ qua các hàng trống
                $validRows = [];
                foreach ($rowsData as $row) {
                    $hasContent = false;
                    foreach ($row as $val) {
                        if (trim((string)$val) !== '') {
                            $hasContent = true;
                            break;
                        }
                    }
                    if ($hasContent) {
                        $validRows[] = array_map(function($v) {
                            return trim((string)$v);
                        }, $row);
                    }
                }

                return [
                    'headers' => $headers,
                    'rows' => $validRows,
                    'total_rows' => count($validRows)
                ];
            } catch (Exception $e) {
                throw new Exception("Lỗi khi đọc file bằng PhpSpreadsheet: " . $e->getMessage());
            }
        }

        // Cách 2: Phân tích file OpenXML .xlsx trực tiếp bằng ZipArchive và SimpleXML
        if (class_exists('ZipArchive') && class_exists('SimpleXMLElement')) {
            return self::parseXlsxDirectXml($filePath);
        }

        // Nếu cả 2 cách đều chưa có: thông báo hướng dẫn rõ ràng
        throw new Exception(
            "Hệ thống chưa tìm thấy thư viện PhpSpreadsheet để đọc file .xlsx.<br>" .
            "<strong>Cách xử lý:</strong><br>" .
            "1. Chạy lệnh cài đặt bằng PowerShell tại thư mục dự án:<br>" .
            "<code>composer require phpoffice/phpspreadsheet</code><br>" .
            "2. Hoặc xuất/lưu file Excel dưới định dạng <strong>CSV (UTF-8)</strong> để nhập dữ liệu ngay lập tức!"
        );
    }

    /**
     * Đọc trực tiếp cấu trúc XML của file .xlsx (OpenXML chuẩn)
     */
    private static function parseXlsxDirectXml(string $filePath): array {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Không thể giải nén file .xlsx. File có thể bị hỏng.");
        }

        // Đọc chuỗi dùng chung (sharedStrings.xml)
        $sharedStrings = [];
        $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringXml !== false) {
            $xml = simplexml_load_string($sharedStringXml);
            if ($xml) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string)$val->t;
                    } elseif (isset($val->r)) {
                        $str = '';
                        foreach ($val->r as $run) {
                            $str .= (string)$run->t;
                        }
                        $sharedStrings[] = $str;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // Đọc dữ liệu sheet1 (xl/worksheets/sheet1.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new Exception("Không tìm thấy sheet dữ liệu trong file .xlsx.");
        }

        $xmlSheet = simplexml_load_string($sheetXml);
        $zip->close();

        if (!$xmlSheet || !isset($xmlSheet->sheetData)) {
            throw new Exception("Dữ liệu trong sheet Excel không hợp lệ.");
        }

        $allRows = [];
        foreach ($xmlSheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $cellType = (string)$cell['t'];
                $val = (string)$cell->v;
                if ($cellType === 's') { // Kiểu shared string
                    $idx = (int)$val;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($cellType === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string)$cell->is->t;
                }
                $rowData[] = trim($val);
            }
            if (!empty(array_filter($rowData))) {
                $allRows[] = $rowData;
            }
        }

        if (empty($allRows)) {
            throw new Exception("File Excel không có dữ liệu.");
        }

        $headers = array_shift($allRows);
        return [
            'headers' => $headers,
            'rows' => $allRows,
            'total_rows' => count($allRows)
        ];
    }
}
