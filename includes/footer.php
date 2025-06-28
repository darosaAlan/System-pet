</div>

    <footer class="footer mt-auto py-3">
        <div class="container text-center">
            <span class="text-muted">
                <i class="fas fa-paw me-1"></i>Sistema de Gerenciamento de Pets &copy; <?php echo date('Y'); ?>
            </span>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Confirmação para exclusões
        function confirmarExclusao(nome) {
            return confirm('Tem certeza que deseja excluir "' + nome + '"?');
        }
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>