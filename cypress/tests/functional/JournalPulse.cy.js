/**
 * @file cypress/tests/functional/JournalPulse.cy.js
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional tests: the theme enables, becomes the journal's theme, and the
 * home page and a published article render with it (a template error is a 500,
 * which fails cy.visit).
 *
 * Parameters (--env): contextPath, adminUser, adminPassword, themePath (captcha
 * on login must be off for the run). The defaults match the data set of PKP's
 * continuous integration; themePath is the theme directory, named after
 * version.xml (journalPulse).
 */

describe('Journal Pulse theme', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const adminUser = Cypress.env('adminUser') || 'admin';
	const adminPassword = Cypress.env('adminPassword') || 'admin';
	const themePath = Cypress.env('themePath') || 'journalPulse';

	const rowName = 'journalpulsethemeplugin';

	// ---- helpers from OJSBR/viewcounter: work on OJS 3.3, 3.4 and 3.5 and in PKP's CI ----

	const pageUrl = (path) => '/index.php/' + contextPath + (path ? '/' + path : '');

	// Same as PKP's cy.waitJQuery(), which the support files of OJS 3.3 test sites may lack.
	// The Plugins tab can keep requests open for a while (the plugin gallery), hence the timeout.
	const waitJQuery = () => cy.window().its('jQuery.active', {timeout: 60000}).should('eq', 0);

	// Requests carry the browser's User-Agent: OJS 3.3 drops a session whose agent changes.
	const request = (options) => cy.window({log: false}).then((win) => cy.request(Object.assign(
		typeof options === 'string' ? {url: options} : options,
		{headers: Object.assign({'User-Agent': win.navigator.userAgent}, (typeof options === 'string' ? {} : options.headers) || {})}
	)));

	// Signs in through requests (the login page can re-render while it is typed into), then
	// falls back to the form when the session did not stick (OJS 3.3 cookie handling).
	const login = (username, password) => {
		cy.clearCookies();
		request(pageUrl('login')).then((response) => {
			const token = /name="csrfToken" value="([^"]+)"/.exec(response.body)[1];
			// The form posts to the URL with the language: a redirect would turn the POST into a GET.
			const action = /<form[^>]*id="login"[^>]*action="([^"]+)"/.exec(response.body)[1];
			request({method: 'POST', url: action, form: true, body: {csrfToken: token, username: username, password: password}, log: false});
		});
		cy.visit(pageUrl('submissions') + '?reload=' + Date.now());
		cy.get('body').then(($body) => {
			if ($body.find('form#login').length) {
				cy.get('form#login input[name="username"]').type(username, {delay: 0});
				cy.get('form#login input[name="password"]').type(password, {delay: 0, log: false});
				cy.get('form#login').submit();
				cy.get('form#login', {timeout: 30000}).should('not.exist');
			}
		});
	};

	// REST API calls made from the page itself, so they carry the browser's own session.
	const api = (path, options = {}) => cy.window({log: false}).then((win) => cy.wrap(
		win.fetch(path, Object.assign({credentials: 'same-origin'}, options)).then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					throw new Error(path + ' answered ' + response.status + ': ' + text.slice(0, 300));
				});
			}
			return response.json();
		}),
		{log: false, timeout: 30000}
	));

	// The website settings page on its Plugins tab (a new query string forces a load). Load it
	// once per test: loading it again while its plugin gallery request is pending stalls the
	// web server of PKP's CI; API calls and settings modals work on the page already open.
	const openPluginsTab = () => {
		cy.visit(pageUrl('management/settings/website') + '?reload=' + Date.now() + '#plugins');
		cy.get('button[id="plugins-button"]', {timeout: 60000}).click();
		cy.get('button[id="plugins-button"]').should('have.attr', 'aria-selected', 'true');
		waitJQuery();
	};

	// Enables the plugin in the grid when it is off (never turns it off).
	const enablePlugin = (rowName) => {
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]', {timeout: 30000}).then(($checkbox) => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				waitJQuery();
			}
		});
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]').should('be.checked');
	};

	// Opens the settings modal from the grid, without reloading the page: a reload right
	// after saving can stall the web server of PKP's CI. The form is fetched each time.
	const openPluginSettings = (rowName, formSelector) => {
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]', {timeout: 30000}).then(($link) => {
			if (!$link.is(':visible')) {
				cy.get('tr[id$="-row-' + rowName + '"] a.show_extras').first().click();
			}
		});
		// The grid may still be animating the extras row: the link is clicked once it exists.
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]').first().click({force: true});
		waitJQuery();
		cy.window().should((win) => {
			expect(win.jQuery(formSelector).data('pkp.handler')).to.exist;
		});
	};

	// ---- end of helpers ----

	// Same request as Settings > Website > Appearance > Theme.
	const useTheme = (path) => cy.window().then((win) => {
		const csrfToken = win.pkp.currentUser.csrfToken;
		api(pageUrl('api/v1/contexts')).then((data) => {
			const context = data.items.find((item) => item.urlPath === contextPath);
			api(pageUrl('api/v1/contexts/' + context.id + '/theme'), {
				method: 'PUT',
				headers: {'Content-Type': 'application/json', 'X-Csrf-Token': csrfToken},
				body: JSON.stringify({themePluginPath: path}),
			});
		});
	});

	it('Enables the theme and makes it the journal theme', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		enablePlugin(rowName);
		useTheme(themePath);
	});

	it('Renders the journal home page', function() {
		cy.visit(pageUrl(''));
		cy.get('.jm_wrap').should('exist');
		// Journal information is on by default and always has the journal title row
		cy.get('.jm_info .jm_info_row').should('have.length.at.least', 1);
	});

	it('Renders a published article', function() {
		login(adminUser, adminPassword);
		api(pageUrl('api/v1/submissions?status=3&count=1')).then((data) => {
			expect(data.items).to.have.length(1);
			cy.visit(data.items[0].urlPublished);
		});
		cy.get('.jm_article_page').should('exist');
		cy.get('h1').should('not.be.empty');
	});
});
