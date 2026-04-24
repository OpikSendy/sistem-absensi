<?php
// includes/footer.php
?>
      <footer class="mt-4 pt-4 pb-3 text-center text-muted small border-top">
        &copy; <?= date('Y') ?> Kesatriyan System. All rights reserved.
      </footer>

    </div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/main.js') ?>"></script>

<?php if (is_logged_in() && !is_admin()): ?>
<div class="modal fade" id="notifModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-dark">Pemberitahuan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-dark" id="notifModalBody"></div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const API = '<?= url('user/api_notifications.php') ?>';
  async function checkNotif() {
    try {
      const res = await fetch(API);
      const json = await res.json();
      
      if (json.ok && json.notifications.length > 0) {
        let html = '';
        json.notifications.forEach(n => {
          html += `<div class="alert alert-info mb-2 border-0 bg-light text-dark">
            <div class="fw-bold">${n.title}</div>
            <div class="small">${n.message}</div>
          </div>`;
        });
        
        const body = document.getElementById('notifModalBody');
        if(body) {
            body.innerHTML = html;
            new bootstrap.Modal(document.getElementById('notifModal')).show();
        }
      }
    } catch (e) { 
        // Silent fail
    }
  }
  setTimeout(checkNotif, 2000);
})();
</script>
<?php endif; ?>

</body>
</html>