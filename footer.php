        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-4 mt-auto">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div class="text-muted small">
                    <span class="fw-semibold text-dark">Korean Vocab Master</span> &copy; <?= date('Y') ?> &mdash; Hệ thống học & kiểm tra từ vựng tiếng Hàn cho người Việt.
                </div>
                <div class="d-flex gap-3 text-muted small">
                    <a href="study.php" class="text-decoration-none text-muted hover-primary">Kiểm tra từ vựng</a>
                    <span>&bull;</span>
                    <a href="vocabulary.php" class="text-decoration-none text-muted hover-primary">Quản lý từ vựng</a>
                    <span>&bull;</span>
                    <a href="import.php" class="text-decoration-none text-muted hover-primary">Nhập CSV/Excel</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Toast Container cho thông báo -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i id="toastIcon" class="bi bi-info-circle-fill fs-5"></i>
                    <span id="toastMessage" class="fw-medium">Nội dung thông báo</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Application JS -->
    <script src="assets/js/app.js"></script>
    <?php if (isset($extraScripts) && is_array($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
