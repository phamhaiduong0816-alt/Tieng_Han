/**
 * JavaScript Quản lý Lượt học và Kiểm tra Từ vựng (Study / Quiz)
 * Korean Vocab Master - Giai đoạn 1
 */

document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const topicSelect = document.getElementById('topicSelect');
    const btnRestart = document.getElementById('btnRestart');
    const btnPlayAgain = document.getElementById('btnPlayAgain');
    const studyActiveState = document.getElementById('studyActiveState');
    const emptyVocabState = document.getElementById('emptyVocabState');
    const finishState = document.getElementById('finishState');

    // Quiz Elements
    const studyProgressBar = document.getElementById('studyProgressBar');
    const progressText = document.getElementById('progressText');
    const badgeTopic = document.getElementById('badgeTopic');
    const badgeWordType = document.getElementById('badgeWordType');
    const badgeCharCount = document.getElementById('badgeCharCount');
    const vietnamesePrompt = document.getElementById('vietnamesePrompt');
    const koreanInput = document.getElementById('koreanInput');
    const feedbackContainer = document.getElementById('feedbackContainer');
    const feedbackIcon = document.getElementById('feedbackIcon');
    const feedbackMessage = document.getElementById('feedbackMessage');
    const extraInfoContainer = document.getElementById('extraInfoContainer');
    const correctKoreanText = document.getElementById('correctKoreanText');
    const pronunciationText = document.getElementById('pronunciationText');
    const exampleBox = document.getElementById('exampleBox');
    const exampleKorean = document.getElementById('exampleKorean');
    const exampleVietnamese = document.getElementById('exampleVietnamese');
    const hintContainer = document.getElementById('hintContainer');
    const hintContent = document.getElementById('hintContent');
    const hintLevelText = document.getElementById('hintLevelText');
    const btnHint = document.getElementById('btnHint');
    const btnEndEarly = document.getElementById('btnEndEarly');
    const btnCheck = document.getElementById('btnCheck');
    const btnNext = document.getElementById('btnNext');

    // Summary Elements
    const summaryTotalWords = document.getElementById('summaryTotalWords');
    const summaryAccuracy = document.getElementById('summaryAccuracy');

    // State Variables
    let words = [];
    let currentIndex = 0;
    let completedCount = 0;
    let currentWordResolved = false;
    let isChecking = false;
    let hintLevel = 0;
    let attemptsOnCurrentWord = 0;
    let firstTryCorrectCount = 0;

    // 1. Khởi tạo danh sách chủ đề
    async function loadTopics() {
        try {
            const res = await fetch('api/vocabulary.php?action=topics');
            const data = await res.json();
            if (data.success && Array.isArray(data.topics)) {
                // Giữ lại option "all"
                topicSelect.innerHTML = '<option value="all">Tất cả chủ đề</option>';
                data.topics.forEach(topic => {
                    const opt = document.createElement('option');
                    opt.value = topic;
                    opt.textContent = topic;
                    topicSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.warn('Lỗi nạp chủ đề:', e);
        }
    }

    // 2. Khởi tạo lượt học mới
    async function initStudySession() {
        const selectedTopic = topicSelect.value || 'all';
        words = [];
        currentIndex = 0;
        completedCount = 0;
        firstTryCorrectCount = 0;

        // Reset UI states
        emptyVocabState.classList.add('d-none');
        finishState.classList.add('d-none');
        studyActiveState.classList.remove('d-none');

        try {
            const url = `api/vocabulary.php?action=study&topic=${encodeURIComponent(selectedTopic)}&limit=25`;
            const res = await fetch(url);
            const data = await res.json();

            if (!data.success || !Array.isArray(data.words) || data.words.length === 0) {
                // Không có từ vựng nào
                studyActiveState.classList.add('d-none');
                emptyVocabState.classList.remove('d-none');
                return;
            }

            words = data.words;
            renderCurrentWord();
        } catch (err) {
            console.error('Lỗi khi tải từ vựng:', err);
            studyActiveState.classList.add('d-none');
            emptyVocabState.classList.remove('d-none');
        }
    }

    // 3. Hiển thị từ vựng hiện tại
    function renderCurrentWord() {
        if (currentIndex >= words.length) {
            showFinishScreen();
            return;
        }

        const currentWord = words[currentIndex];
        currentWordResolved = false;
        attemptsOnCurrentWord = 0;
        hintLevel = 0;

        // Cập nhật thông tin thẻ
        vietnamesePrompt.textContent = currentWord.vietnamese;
        badgeTopic.textContent = currentWord.topic || 'Cơ bản';
        badgeWordType.textContent = currentWord.word_type || 'Từ vựng';
        badgeCharCount.textContent = `${currentWord.char_length || '?'} ký tự`;

        // Cập nhật thanh tiến trình
        const progressPercent = Math.round((completedCount / words.length) * 100);
        studyProgressBar.style.width = `${progressPercent}%`;
        progressText.textContent = `${completedCount} / ${words.length} từ`;

        // Reset input và các thông báo
        koreanInput.value = '';
        koreanInput.disabled = false;
        koreanInput.className = 'form-control korean-input font-korean';
        koreanInput.focus();

        // Ẩn feedback và chi tiết
        feedbackContainer.className = 'feedback-box d-flex align-items-center justify-content-center gap-2 p-3 mb-3 d-none';
        extraInfoContainer.classList.add('d-none');
        hintContainer.classList.add('d-none');
        hintLevelText.textContent = '1/3';

        // Đổi trạng thái nút bấm
        btnCheck.classList.remove('d-none');
        btnNext.classList.add('d-none');
    }

    // 4. Xử lý kiểm tra đáp án
    async function checkAnswer() {
        if (isChecking || currentWordResolved) return;

        const currentWord = words[currentIndex];
        if (!currentWord) return;

        const userAnswer = koreanInput.value.trim();

        if (!userAnswer) {
            showFeedback(false, 'Vui lòng nhập từ tiếng Hàn trước khi kiểm tra!');
            koreanInput.focus();
            return;
        }

        isChecking = true;
        attemptsOnCurrentWord++;

        try {
            const res = await fetch('api/check_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    vocab_id: currentWord.id,
                    user_answer: userAnswer
                })
            });

            const data = await res.json();

            if (data.is_correct) {
                // ĐÚNG:
                currentWordResolved = true;
                completedCount++;
                if (attemptsOnCurrentWord === 1) {
                    firstTryCorrectCount++;
                }

                showFeedback(true, data.message || 'Chính xác! Rất tốt!');
                koreanInput.classList.remove('is-wrong-border');
                koreanInput.classList.add('is-correct-border');
                koreanInput.disabled = true;

                // Cập nhật thanh tiến trình ngay khi đúng
                const progressPercent = Math.round((completedCount / words.length) * 100);
                studyProgressBar.style.width = `${progressPercent}%`;
                progressText.textContent = `${completedCount} / ${words.length} từ`;

                // Hiển thị thông tin củng cố kiến thức (phát âm, ví dụ)
                if (data.details) {
                    correctKoreanText.textContent = data.details.korean;
                    pronunciationText.textContent = data.details.pronunciation ? `[${data.details.pronunciation}]` : '';
                    if (data.details.example) {
                        exampleKorean.textContent = data.details.example;
                        exampleVietnamese.textContent = data.details.example_translation || '';
                        exampleBox.classList.remove('d-none');
                    } else {
                        exampleBox.classList.add('d-none');
                    }
                    extraInfoContainer.classList.remove('d-none');
                }

                // Ẩn nút Kiểm tra, hiện nút Từ tiếp theo
                btnCheck.classList.add('d-none');
                btnNext.classList.remove('d-none');
                btnNext.focus();

            } else {
                // SAI:
                showFeedback(false, data.message || 'Chưa chính xác, hãy thử lại!');
                koreanInput.classList.add('is-wrong-border');
                koreanInput.select(); // Bôi đen để người học gõ lại thuận tiện
            }

        } catch (err) {
            console.error('Lỗi khi kiểm tra:', err);
            showFeedback(false, 'Không thể kết nối đến máy chủ. Hãy thử lại!');
        } finally {
            isChecking = false;
        }
    }

    // 5. Hiển thị thông báo phản hồi Đúng/Sai
    function showFeedback(isCorrect, message) {
        feedbackContainer.classList.remove('d-none', 'feedback-success', 'feedback-wrong');
        feedbackIcon.className = 'mark-icon';

        if (isCorrect) {
            feedbackContainer.classList.add('feedback-success');
            feedbackIcon.classList.add('mark-success');
            feedbackIcon.innerHTML = '<i class="bi bi-check-lg"></i>';
            feedbackMessage.textContent = message;
        } else {
            feedbackContainer.classList.add('feedback-wrong');
            feedbackIcon.classList.add('mark-wrong');
            feedbackIcon.innerHTML = '<i class="bi bi-x-lg"></i>';
            feedbackMessage.textContent = message;
        }
    }

    // 6. Xử lý yêu cầu Gợi ý
    async function requestHint() {
        if (currentWordResolved) return;

        const currentWord = words[currentIndex];
        if (!currentWord) return;

        if (hintLevel < 3) {
            hintLevel++;
        }

        try {
            const res = await fetch(`api/check_answer.php?action=hint&vocab_id=${currentWord.id}&level=${hintLevel}`);
            const data = await res.json();

            if (data.success && data.hint) {
                hintContent.textContent = data.hint;
                hintLevelText.textContent = `${hintLevel}/3`;
                hintContainer.classList.remove('d-none');
                koreanInput.focus();
            }
        } catch (e) {
            console.error('Lỗi lấy gợi ý:', e);
        }
    }

    // 7. Chuyển sang từ tiếp theo
    function advanceToNextWord() {
        currentIndex++;
        renderCurrentWord();
    }

    // 8. Màn hình hoàn thành
    function showFinishScreen() {
        studyActiveState.classList.add('d-none');
        finishState.classList.remove('d-none');

        const total = words.length;
        const accuracy = total > 0 ? Math.round((firstTryCorrectCount / total) * 100) : 100;

        summaryTotalWords.textContent = `${completedCount} / ${total}`;
        summaryAccuracy.textContent = `${accuracy}%`;
    }

    // 9. Lắng nghe sự kiện bàn phím (Phím Enter)
    koreanInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (currentWordResolved) {
                advanceToNextWord();
            } else {
                checkAnswer();
            }
        }
    });

    // Khi đã giải quyết xong từ, nhấn Enter bất kỳ đâu trên form cũng chuyển từ tiếp theo
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && currentWordResolved && !btnNext.classList.contains('d-none')) {
            e.preventDefault();
            advanceToNextWord();
        }
    });

    // Sự kiện các nút bấm
    btnCheck.addEventListener('click', checkAnswer);
    btnNext.addEventListener('click', advanceToNextWord);
    btnHint.addEventListener('click', requestHint);

    btnEndEarly.addEventListener('click', function () {
        if (completedCount > 0 || confirm('Bạn có chắc chắn muốn kết thúc lượt học này?')) {
            showFinishScreen();
        }
    });

    topicSelect.addEventListener('change', initStudySession);
    btnRestart.addEventListener('click', initStudySession);
    btnPlayAgain.addEventListener('click', initStudySession);

    // Chạy khởi tạo ban đầu
    loadTopics().then(initStudySession);
});
