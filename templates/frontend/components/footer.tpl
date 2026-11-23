{**
 * templates/frontend/components/footer.tpl
 *
 * Journal Pulse theme. Based on lib/pkp/templates/frontend/components/footer.tpl.
 * Adds a dark footer with journal identity and the main navigation links.
 *
 *
 * @hook Templates::Common::Footer::PageFooter []
 *}

			</main><!-- pkp_structure_main -->

</div><!-- pkp_structure_content -->

<div class="pkp_structure_footer_wrapper" role="contentinfo">
	<a id="pkp_content_footer"></a>

	<div class="pkp_structure_footer">

		{if $currentContext}
			<div class="jm_footer_grid">
				<div class="jm_footer_id">
					<p class="jm_footer_name">{$currentContext->getLocalizedName()|escape}</p>
					{assign var="jmFooterIssn" value=$currentContext->getData('onlineIssn')|default:$currentContext->getData('printIssn')}
					{if $jmFooterIssn}<p class="jm_footer_meta">{translate key="plugins.themes.journalPulse.footer.issn" issn=$jmFooterIssn|escape}</p>{/if}
					{if $currentContext->getData('publisherInstitution')}<p class="jm_footer_meta">{$currentContext->getData('publisherInstitution')|escape}</p>{/if}
				</div>
				<nav class="jm_footer_nav" aria-label="{translate|escape key="common.navigation.site"}">
					<ul>
						<li><a href="{url page="issue" op="current"}">{translate key="journal.currentIssue"}</a></li>
						<li><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
						<li><a href="{url page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
						<li><a href="{url page="about" op="editorialMasthead"}">{translate key="common.editorialMasthead"}</a></li>
						<li><a href="{url page="about" op="contact"}">{translate key="about.contact"}</a></li>
						{if $currentContext->getLocalizedData('readerInformation')}<li><a href="{url page="information" op="readers"}">{translate key="navigation.infoForReaders"}</a></li>{/if}
						{if $currentContext->getLocalizedData('authorInformation')}<li><a href="{url page="information" op="authors"}">{translate key="navigation.infoForAuthors"}</a></li>{/if}
						{if $currentContext->getLocalizedData('librarianInformation')}<li><a href="{url page="information" op="librarians"}">{translate key="navigation.infoForLibrarians"}</a></li>{/if}
					</ul>
				</nav>
			</div>
		{/if}

		{if $pageFooter}
			<div class="pkp_footer_content">
				{$pageFooter}
			</div>
		{/if}

		<div class="jm_footer_bottom">
			<div class="pkp_brand_footer">
				<a href="{url page="about" op="aboutThisPublishingSystem"}">
					<img alt="{translate key="about.aboutThisPublishingSystem"}" src="{$baseUrl}/{$brandImage}">
				</a>
			</div>
		</div>
	</div>
</div><!-- pkp_structure_footer_wrapper -->

</div><!-- pkp_structure_page -->

{load_script context="frontend"}

{* Show/hide button on every password field (login, registration, password change); core templates stay untouched *}
<script>window.jmPwText={ldelim}show: "{translate|escape:"javascript" key="plugins.themes.journalPulse.password.show"}", hide: "{translate|escape:"javascript" key="plugins.themes.journalPulse.password.hide"}"{rdelim};</script>
{literal}<script>
(function (d) {
	var T = window.jmPwText;
	var icons = '<svg class="jm_pw_eye" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>'
		+ '<svg class="jm_pw_eye_off" aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.9 3.8M6.6 6.6C3.7 8.4 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="m2 2 20 20"/></svg>';
	Array.prototype.forEach.call(d.querySelectorAll('input[type="password"]'), function (input) {
		var wrap = d.createElement('span'), btn = d.createElement('button');
		wrap.className = 'jm_pw';
		input.parentNode.insertBefore(wrap, input);
		wrap.appendChild(input);
		btn.type = 'button';
		btn.className = 'jm_pw_toggle';
		btn.innerHTML = icons;
		if (input.id) { btn.setAttribute('aria-controls', input.id); }
		function set(shown) {
			input.type = shown ? 'text' : 'password';
			btn.setAttribute('aria-pressed', shown ? 'true' : 'false');
			btn.setAttribute('aria-label', shown ? T.hide : T.show);
			btn.title = shown ? T.hide : T.show;
		}
		set(false);
		btn.addEventListener('click', function () {
			set(input.type === 'password');
			input.focus();
		});
		// Password managers expect a password field when the form is sent
		if (input.form) { input.form.addEventListener('submit', function () { set(false); }); }
		wrap.appendChild(btn);
	});
})(document);
</script>{/literal}

{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>
