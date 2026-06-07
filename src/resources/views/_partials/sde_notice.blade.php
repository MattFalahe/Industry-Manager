{{-- Reusable banner shown when the industryActivity* SDE tables aren't present.
     Industry Manager registers those tables with SeAT's SDE updater; the
     operator just needs to run the update once. --}}
<div class="im-sde-notice">
    <h4><i class="fas fa-triangle-exclamation mr-2"></i> Industry recipe data not loaded yet</h4>
    <p>Industry Manager reads EVE's industry recipes (what each blueprint consumes, produces, and how long it takes) from SeAT's Static Data Export. Those industry tables aren't in your database yet.</p>
    <p>An administrator needs to run SeAT's SDE update once, from the SeAT server:</p>
    <pre>php artisan eve:update:sde --force</pre>
    <p class="im-text-muted">This downloads the industry tables (<code>industryActivityMaterials</code>, <code>industryActivityProducts</code>, and friends) alongside SeAT's normal static data, in the same format SeAT already uses. No ESI, API keys, or external accounts are involved. Once it finishes, reload this page and everything lights up.</p>
</div>
