<style>
    .footer-text,
    .footer-text-reserved {
        color: black;
    }
</style>
<footer>
    <figure class="footer-figure">
        <a href="https://teymauruguay.sharepoint.com/sites/intranet" target="_blank">
            <img src="/gestioncompras/img/Etarey.png" alt="Logo Etarey" />
        </a>
        <p class="footer-text">&copy; <span id="year"></span> Web creada por IT Etarey</p>
        <p class="footer-text">Todos los derechos Reservados</p>
    </figure>
</footer>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Update the year in the footer
        document.getElementById('year').textContent = new Date().getFullYear();
    });
</script>