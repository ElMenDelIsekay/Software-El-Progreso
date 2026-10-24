/* ========== BOTÓN IR ARRIBA ========== */
(function () {
    document.addEventListener('DOMContentLoaded', function () {

        const btn = document.getElementById('btn-arriba');
        if (!btn) return;

        function toggleBoton() {
            if (window.scrollY > 200) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }

        window.addEventListener('scroll', toggleBoton);
        toggleBoton();

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
})();