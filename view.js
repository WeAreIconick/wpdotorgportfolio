"use strict";
document.addEventListener('click', function(e) {
	// Load More handling
	const loadMoreBtn = e.target.closest('.wp-block-w0-wp-org-portfolio .was-load-more-btn');
	if (loadMoreBtn) {
		e.preventDefault();
		const blockRoot = loadMoreBtn.closest('.wp-block-w0-wp-org-portfolio');
		if (!blockRoot) return;
		const username = blockRoot.getAttribute('data-username');
		const showtype = blockRoot.getAttribute('data-showtype');
		const perPage = parseInt(blockRoot.getAttribute('data-per-page'), 10) || 10;
		const jsattr = loadMoreBtn.getAttribute('data-jsattr'); // 'plugins' or 'themes'
		let currentCount = parseInt(blockRoot.getAttribute('data-loaded-count'), 10) || perPage;
		const nextCount = currentCount + perPage;

		fetch(`/wp-json/wporg-showcase/v1/author/${encodeURIComponent(username)}`)
		.then(r => r.json())
		.then(data => {
			if (!data) return;
			if (jsattr === 'plugins') {
				const plugins = Array.isArray(data.plugins) ? data.plugins : [];
				const toShow = plugins.slice(0, nextCount);
				let html = '';
				if (toShow.length) {
					html = toShow.map((plugin) => {
						const iconUrl = plugin.icons && plugin.icons['1x'] ? plugin.icons['1x'] : (plugin.icons && plugin.icons['default'] ? plugin.icons['default'] : '');
						const avgRating = plugin.rating && plugin.num_ratings ? (Number(plugin.rating) / 20) : null;
						const name = plugin.name;
						const desc = plugin.short_description || '';
						const learnMoreUrl = `https://wordpress.org/plugins/${plugin.slug}/`;
						return `<div class='was-card' style='position:relative;'>${iconUrl ? `<img src='${iconUrl}' alt='${name}' />` : ''}<div style='flex:1;position:relative;min-height:80px;'><a href='${learnMoreUrl}' target='_blank' rel='noopener noreferrer' style='font-weight:bold;font-size:1.2rem;color:#262626;text-decoration:none;'>${name}</a>${avgRating ? `<div style='margin:0.2rem 0 0 0;display:flex;align-items:center;gap:8px;'><span style='color:#ffc844;font-size:1.08rem;letter-spacing:0.05em;'>${'★'.repeat(Math.floor(avgRating))}${'☆'.repeat(5 - Math.ceil(avgRating))}</span><span style='font-size:0.95rem;color:#6e6e6e;'>${avgRating.toFixed(1)} / 5</span><span style='font-size:0.93rem;color:#aaa;'>(${plugin.num_ratings} ratings)</span></div>` : ''}${desc ? `<p style='margin:0.5rem 0 0.25rem 0;font-size:0.98rem;color:#555;'>${desc}</p>` : ''}${plugin.active_installs ? `<div style='font-size:0.95rem;color:#999;margin-top:0.5rem;'>${plugin.active_installs.toLocaleString()}+ installs</div>` : ''}<a href='${learnMoreUrl}' target='_blank' rel='noopener noreferrer' class='was-learn-more' style='position:absolute;right:0;bottom:0;font-size:0.98rem;color:#2271b1;text-decoration:underline;font-weight:500;padding:2px 4px;z-index:10;'>Learn more</a></div></div>`;
					}).join('');
				} else {
					html = `<div>No plugins found.</div>`;
				}
				const pluginsGrid = blockRoot.querySelector('.was-grid');
				if (pluginsGrid) pluginsGrid.innerHTML = html;
				if (toShow.length >= plugins.length) {
					loadMoreBtn.remove();
				} else {
					blockRoot.setAttribute('data-loaded-count', nextCount);
				}
			}
			if (jsattr === 'themes') {
				const themes = Array.isArray(data.themes) ? data.themes : [];
				const toShow = themes.slice(0, nextCount);
				let html = '';
				if (toShow.length) {
					html = toShow.map((theme) => {
						const screenshot = theme.screenshot_url || '';
						const name = theme.name;
						let desc = theme.description || '';
						if (desc.length > 100) desc = desc.substring(0,100) + '...';
						const learnMoreUrl = `https://wordpress.org/themes/${theme.slug}/`;
						return `<div class='was-card' style='position:relative;'>${screenshot ? `<img src='${screenshot}' alt='${name}' />` : ''}<div style='flex:1;position:relative;min-height:80px;'><a href='${learnMoreUrl}' target='_blank' rel='noopener noreferrer' style='font-weight:bold;font-size:1.2rem;color:#262626;text-decoration:none;'>${name}</a>${desc ? `<p style='margin:0.5rem 0 0.25rem 0;font-size:0.98rem;color:#555;'>${desc}</p>` : ''}<a href='${learnMoreUrl}' target='_blank' rel='noopener noreferrer' class='was-learn-more' style='position:absolute;right:0;bottom:0;font-size:0.98rem;color:#2271b1;text-decoration:underline;font-weight:500;padding:2px 4px;z-index:10;'>Learn more</a></div></div>`;
					}).join('');
				} else {
					html = `<div>No themes found.</div>`;
				}
				const themesGrid = blockRoot.querySelector('.was-grid');
				if (themesGrid) themesGrid.innerHTML = html;
				if (toShow.length >= themes.length) {
					loadMoreBtn.remove();
				} else {
					blockRoot.setAttribute('data-loaded-count', nextCount);
				}
			}
		});
		return;
	}
});
