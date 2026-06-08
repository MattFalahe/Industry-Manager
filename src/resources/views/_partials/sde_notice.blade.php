{{-- Reusable banner shown when the industry/PI recipe tables aren't present.
     Industry Manager imports these itself via its own command — it does NOT
     touch SeAT's core SDE. --}}
<div class="im-sde-notice">
    <h4><i class="fas fa-triangle-exclamation mr-2"></i> Recipe data not imported yet</h4>
    <p>Industry Manager needs EVE's industry &amp; planetary recipe tables (what each blueprint or schematic consumes and produces). These aren't part of SeAT's core data, so the plugin imports them itself.</p>
    <p>An administrator runs this once on the SeAT server (re-run after an EVE patch to refresh):</p>
    <pre>php artisan industry-manager:import-sde</pre>
    <p class="im-text-muted">It downloads only Industry Manager's tables (a few small files) from Fuzzwork and imports them directly. It does <strong>not</strong> re-download or touch SeAT's core SDE, and uses no ESI or API keys. Once it finishes, reload this page.</p>
</div>
