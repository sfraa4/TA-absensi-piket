    <?php if (basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
    </div>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable();
            
            function updateAdminClock() {
                const clockEl = document.getElementById('admin_clock');
                if(clockEl) {
                    const now = new Date();
                    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    
                    const dayName = days[now.getDay()];
                    const day = now.getDate();
                    const month = months[now.getMonth()];
                    const year = now.getFullYear();
                    
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    const seconds = now.getSeconds().toString().padStart(2, '0');
                    
                    clockEl.textContent = `${dayName}, ${day} ${month} ${year} | ${hours}:${minutes}:${seconds} WIB`;
                }
            }
            updateAdminClock();
            setInterval(updateAdminClock, 1000);
        });
    </script>
</body>
</html>
