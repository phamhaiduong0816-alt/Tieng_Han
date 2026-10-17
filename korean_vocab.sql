-- ========================================================
-- KOREAN VOCAB MASTER - DATABASE SCHEMA
-- Hệ thống học và kiểm tra từ vựng tiếng Hàn cho người Việt
-- ========================================================

CREATE DATABASE IF NOT EXISTS `korean_vocab` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `korean_vocab`;

-- --------------------------------------------------------
-- 1. Bảng vocabulary: Lưu từ vựng chính và thông tin chi tiết
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vocabulary` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `korean` VARCHAR(255) NOT NULL COMMENT 'Từ tiếng Hàn chuẩn',
  `vietnamese` VARCHAR(255) NOT NULL COMMENT 'Nghĩa tiếng Việt',
  `pronunciation` VARCHAR(255) DEFAULT '' COMMENT 'Phiên âm / Romanization',
  `word_type` VARCHAR(50) DEFAULT 'Danh từ' COMMENT 'Loại từ: Danh từ, Động từ, Tính từ...',
  `example` TEXT DEFAULT NULL COMMENT 'Câu ví dụ tiếng Hàn',
  `example_translation` TEXT DEFAULT NULL COMMENT 'Dịch câu ví dụ tiếng Việt',
  `topic` VARCHAR(100) DEFAULT 'Cơ bản' COMMENT 'Chủ đề / Bài học',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_korean` (`korean`),
  INDEX `idx_topic` (`topic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Bảng vocabulary_answers: Lưu các đáp án hợp lệ cho mỗi từ
-- (Hỗ trợ từ có nhiều cách viết, kính ngữ hoặc từ đồng nghĩa)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vocabulary_answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vocab_id` INT NOT NULL,
  `answer` VARCHAR(255) NOT NULL COMMENT 'Đáp án tiếng Hàn hợp lệ',
  `is_primary` TINYINT(1) DEFAULT 0 COMMENT '1: Đáp án chính, 0: Đáp án thay thế',
  FOREIGN KEY (`vocab_id`) REFERENCES `vocabulary`(`id`) ON DELETE CASCADE,
  INDEX `idx_vocab_id` (`vocab_id`),
  INDEX `idx_answer` (`answer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Bảng vocabulary_sets: Nhóm các từ thành bộ từ vựng
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vocabulary_sets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL COMMENT 'Tên bộ từ vựng',
  `description` TEXT DEFAULT NULL COMMENT 'Mô tả',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Bảng vocabulary_set_items: Liên kết từ với bộ từ vựng
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vocabulary_set_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `set_id` INT NOT NULL,
  `vocab_id` INT NOT NULL,
  FOREIGN KEY (`set_id`) REFERENCES `vocabulary_sets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vocab_id`) REFERENCES `vocabulary`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `idx_set_vocab` (`set_id`, `vocab_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DỮ LIỆU MẪU BAN ĐẦU (SEED DATA)
-- Đa dạng chủ đề: Chào hỏi, Số đếm, Gia đình, Đồ ăn, Đời sống
-- ========================================================

INSERT INTO `vocabulary` (`id`, `korean`, `vietnamese`, `pronunciation`, `word_type`, `example`, `example_translation`, `topic`) VALUES
(1, '안녕하세요', 'Xin chào', 'an-nyeong-ha-se-yo', 'Câu chào', '안녕하세요! 만나서 반갑습니다.', 'Xin chào! Rất vui được gặp bạn.', 'Chào hỏi'),
(2, '감사합니다', 'Cảm ơn', 'gam-sa-ham-ni-da', 'Câu cảm ơn', '도와주셔서 감사합니다.', 'Cảm ơn vì đã giúp đỡ tôi.', 'Chào hỏi'),
(3, '죄송합니다', 'Xin lỗi', 'joe-song-ham-ni-da', 'Câu xin lỗi', '늦어서 죄송합니다.', 'Xin lỗi vì tôi đến muộn.', 'Chào hỏi'),
(4, '안녕히 계세요', 'Tạm biệt (nói với người ở lại)', 'an-nyeong-hi gye-se-yo', 'Câu chào', '저는 먼저 갑니다. 안녕히 계세요.', 'Tôi xin phép về trước. Tạm biệt.', 'Chào hỏi'),
(5, '안녕히 가세요', 'Tạm biệt (nói với người đi)', 'an-nyeong-hi ga-se-yo', 'Câu chào', '조심히 들어가세요. 안녕히 가세요.', 'Đi cẩn thận nhé. Tạm biệt.', 'Chào hỏi'),
(6, '물', 'Nước', 'mul', 'Danh từ', '시원한 물 한 잔 주세요.', 'Cho tôi một ly nước mát.', 'Đồ ăn uống'),
(7, '밥', 'Cơm', 'bap', 'Danh từ', '오늘 점심에 밥을 먹었어요.', 'Hôm nay tôi đã ăn cơm trưa.', 'Đồ ăn uống'),
(8, '김치', 'Kim chi', 'gim-chi', 'Danh từ', '한국 사람들은 김치를 좋아해요.', 'Người Hàn Quốc rất thích kim chi.', 'Đồ ăn uống'),
(9, '사과', 'Quả táo', 'sa-gwa', 'Danh từ', '아침에 빨간 사과를 먹었습니다.', 'Buổi sáng tôi đã ăn quả táo đỏ.', 'Đồ ăn uống'),
(10, '학교', 'Trường học', 'hak-gyo', 'Danh từ', '저는 매일 아침 학교에 갑니다.', 'Mỗi sáng tôi đều đến trường.', 'Trường học'),
(11, '선생님', 'Thầy giáo/Cô giáo', 'seon-saeng-nim', 'Danh từ', '선생님께서 한국어를 가르쳐 주십니다.', 'Thầy giáo dạy tiếng Hàn cho chúng tôi.', 'Trường học'),
(12, '학생', 'Học sinh/Sinh viên', 'hak-saeng', 'Danh từ', '저는 대학교 2학년 학생입니다.', 'Tôi là sinh viên năm 2 đại học.', 'Trường học'),
(13, '친구', 'Bạn bè', 'chin-gu', 'Danh từ', '주말에 친구와 영화를 볼 거예요.', 'Cuối tuần tôi sẽ đi xem phim cùng bạn.', 'Đời sống'),
(14, '가족', 'Gia đình', 'ga-jok', 'Danh từ', '우리 가족은 모두 네 명입니다.', 'Gia đình tôi có tất cả 4 người.', 'Gia đình'),
(15, '어머니', 'Mẹ', 'eo-meo-ni', 'Danh từ', '어머니께서 맛있는 음식을 만들어 주셨어요.', 'Mẹ đã nấu cho tôi món ăn rất ngon.', 'Gia đình'),
(16, '아버지', 'Bố/Ba', 'a-beo-ji', 'Danh từ', '아버지는 회사에 다니십니다.', 'Bố tôi đi làm ở công ty.', 'Gia đình'),
(17, '사랑하다', 'Yêu', 'sa-rang-ha-da', 'Động từ', '저는 우리 가족을 사랑합니다.', 'Tôi yêu gia đình của tôi.', 'Đời sống'),
(18, '공부하다', 'Học bài', 'gong-bu-ha-da', 'Động từ', '도서관에서 한국어를 공부합니다.', 'Tôi học tiếng Hàn ở thư viện.', 'Trường học'),
(19, '가다', 'Đi', 'ga-da', 'Động từ', '지금 집에 갑니다.', 'Bây giờ tôi đi về nhà.', 'Đời sống'),
(20, '오다', 'Đến/Tới', 'o-da', 'Động từ', '친구가 베트남에서 옵니다.', 'Bạn tôi từ Việt Nam đến.', 'Đời sống');

-- Thêm các đáp án hợp lệ (mỗi từ có ít nhất 1 đáp án chính)
INSERT INTO `vocabulary_answers` (`vocab_id`, `answer`, `is_primary`) VALUES
(1, '안녕하세요', 1),
(1, '안녕', 0),
(2, '감사합니다', 1),
(2, '고맙습니다', 0),
(3, '죄송합니다', 1),
(3, '미안합니다', 0),
(4, '안녕히 계세요', 1),
(5, '안녕히 가세요', 1),
(6, '물', 1),
(7, '밥', 1),
(8, '김치', 1),
(9, '사과', 1),
(10, '학교', 1),
(11, '선생님', 1),
(12, '학생', 1),
(13, '친구', 1),
(14, '가족', 1),
(15, '어머니', 1),
(15, '엄마', 0),
(16, '아버지', 1),
(16, '아빠', 0),
(17, '사랑하다', 1),
(18, '공부하다', 1),
(19, '가다', 1),
(20, '오다', 1);

-- Thêm bộ từ vựng mẫu
INSERT INTO `vocabulary_sets` (`id`, `name`, `description`) VALUES
(1, 'Giao tiếp cơ bản hàng ngày', 'Tổng hợp các câu chào hỏi và từ vựng thông dụng nhất'),
(2, 'Trường học và Học tập', 'Từ vựng chủ đề lớp học, trường học và hành động học tập');

INSERT INTO `vocabulary_set_items` (`set_id`, `vocab_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 13),
(2, 10), (2, 11), (2, 12), (2, 18);
