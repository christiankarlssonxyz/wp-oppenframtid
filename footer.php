<footer class="site-footer">
    <div class="site-footer__inner">

        <p class="site-footer__name"><?php bloginfo('name'); ?></p>

        <nav class="site-footer__nav" aria-label="Sidfotsmeny">
            <?php wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'footer-nav-list',
                'fallback_cb'    => false,
            ]); ?>
        </nav>

        <p class="site-footer__copy">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>

    </div>
</footer>

<?php wp_footer(); ?>

<script>
// Hamburgermeny – öppna/stäng nav på mobil
(function () {
    var btn = document.querySelector('.site-header__menu-btn');
    var nav = document.querySelector('.site-header__nav');
    if (!btn || !nav) return;
    btn.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
</script>

</body>
</html>
