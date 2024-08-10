<!-- Footer -->
<div id="section-not-to-print">
    <footer class="content-footer footer bg-footer-theme mt-2 container-fluid">
        <div class="container-fluid d-flex flex-wrap justify-content-between  flex-md-row flex-column">
            <div class=" mb-md-0 d-flex align-items-start justify-content-between">
                ©
                {{ date('Y') }}
                , <?= $general_settings['footer_text'] ?>
                <p class="ms-2 footer-text">
                    v{{ get_current_version() }}
                </p>
            </div>
        </div>
    </footer>
</div>
<!-- / Footer -->
