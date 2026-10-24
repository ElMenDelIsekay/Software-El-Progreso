
(function () {
    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function () {

        const btn = document.getElementById('btn-arriba');
        if (!btn) return;

        // Mostrar u ocultar según el scroll
        function toggleBoton() {
            if (window.scrollY > 200) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }

        // Escuchar el scroll
        window.addEventListener('scroll', toggleBoton);
        toggleBoton(); // comprobar estado inicial

        // Al hacer clic, subir suavemente
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
})();