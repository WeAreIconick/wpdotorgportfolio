import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useBlockProps, InspectorControls, BlockControls, AlignmentControl } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, Spinner, Notice, Button as WPButton, Panel } from '@wordpress/components';
import './editor.scss';

function decodeAllEntities(str) {
	if (!str) return '';
	let prev = null, curr = str;
	const textarea = document.createElement('textarea');
	for (let i = 0; i < 6; i++) {
		textarea.innerHTML = curr;
		curr = textarea.value;
		if (curr === prev) break;
		prev = curr;
	}
	curr = curr.replace(/&[#A-Za-z0-9]+;/g, '');
	curr = curr.replace(/[\u2010-\u2015\u2212\u2012]/g, '-');
	return curr;
}

function renderStars(avg) {
	if (!avg || isNaN(avg)) return null;
	const stars = [];
	const round = Math.round(avg * 2) / 2;
	for (let i = 1; i <= 5; i++) {
		if (round >= i) stars.push('★');
		else if (round >= i - 0.5) stars.push('☆');
		else stars.push('☆');
	}
	return (
		<span
			style={{ color: '#ffc844', fontSize: '1.08rem', letterSpacing: '0.05em', marginRight: 6 }}
			aria-label={__('Star rating', 'wp-org-portfolio-block-wp')}
		>
			{stars.join('')}
		</span>
	);
}

function PoweredByTelexPanel() {
	return (
		<PanelBody title={__('Powered by Telex', 'wp-org-portfolio-block-wp')} initialOpen={true}>
			<p>Telex is basically the J.A.R.V.I.S of WordPress development - an AI that builds blocks so you don't have to.</p>
			<p style={{marginTop: 6}}>
				<a
					href="https://telex.automattic.ai"
					target="_blank"
					rel="noopener noreferrer"
				>
					Learn more about Telex
				</a>
			</p>
		</PanelBody>
	);
}

export default function Edit({ attributes, setAttributes }) {
	const { username, showType = 'plugins', perPage = 10, align, loadedCount } = attributes;
	const [loading, setLoading] = useState(false);
	const [plugins, setPlugins] = useState([]);
	const [themes, setThemes] = useState([]);
	const [error, setError] = useState('');

	const fetchData = async (name) => {
		if (!name) {
			setPlugins([]);
			setThemes([]);
			setError('');
			return;
		}
		setLoading(true);
		setError('');
		try {
			const res = await wp.apiFetch({
				path: `/wporg-showcase/v1/author/${encodeURIComponent(name)}`,
			});
			let pluginsArr = res.plugins || [];
			let themesArr = res.themes || [];
			if (!Array.isArray(pluginsArr)) pluginsArr = [];
			if (!Array.isArray(themesArr)) themesArr = [];
			setPlugins(pluginsArr);
			setThemes(themesArr);
			if (pluginsArr.length === 0 && themesArr.length === 0) {
				setError(__('No results found for this username.', 'wp-org-portfolio-block-wp'));
			} else {
				setError('');
			}
		} catch (e) {
			setError(__('Failed to fetch data.', 'wp-org-portfolio-block-wp'));
			setPlugins([]);
			setThemes([]);
		}
		setLoading(false);
	};

	useEffect(() => {
		if (username) {
			fetchData(username);
			setAttributes({ ...attributes, loadedCount: undefined });
		} else {
			setPlugins([]);
			setThemes([]);
			setError('');
		}
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [username]);

	const blockProps = useBlockProps({
		className: align ? `align${align}` : undefined
	});

	function handleLoadMore(type, totalCount) {
		const curr = Number(loadedCount) || perPage;
		const newCount = Math.min(curr + perPage, totalCount);
		setAttributes({ ...attributes, loadedCount: newCount });
	}

	function renderPlugins() {
		if (!plugins.length) return <div>{__('No plugins found.', 'wp-org-portfolio-block-wp')}</div>;
		const toShow = Number(loadedCount) || perPage;
		const showPlugins = plugins.slice(0, toShow);
		return (
			<div className="was-grid">
			{showPlugins.map((plugin) => {
				const iconUrl = plugin.icons && plugin.icons['1x']
					? plugin.icons['1x']
					: plugin.icons && plugin.icons['default']
						? plugin.icons['default']
						: '';
				const rating = plugin.rating && plugin.num_ratings ? Number(plugin.rating) : null;
				const avgRating = rating ? (rating / 20) : null;
				const name = decodeAllEntities(plugin.name);
				const desc = decodeAllEntities(plugin.short_description);
				const learnMoreUrl = `https://wordpress.org/plugins/${plugin.slug}/`;
				return (
					<div key={plugin.slug} className="was-card">
						{iconUrl && <img src={iconUrl} alt={name} />}
						<div className="was-card-content">
							<a href={learnMoreUrl} target="_blank" rel="noopener noreferrer">{name}</a>
							{avgRating && (
								<div className="was-rating">
									{renderStars(avgRating)}
									<span className="was-rating-numeric">{avgRating.toFixed(1)} / 5</span>
									<span className="was-num-ratings">({plugin.num_ratings} ratings)</span>
								</div>
							)}
							{desc && <p>{desc}</p>}
							{plugin.active_installs && (
								<div className="was-meta">{plugin.active_installs.toLocaleString()}+ installs</div>
							)}
							<WPButton
								onClick={() => window.open(learnMoreUrl, '_blank')}
								className="was-learn-more"
								tabIndex={0}
							>
								{__('Learn more', 'wp-org-portfolio-block-wp')}
							</WPButton>
						</div>
					</div>
				);
			})}
			{showPlugins.length < plugins.length && (
				<div style={{ display: 'flex', justifyContent: 'center' }}>
					<WPButton
						className="was-load-more-btn"
						style={{ margin: '0 0 8px 0', padding: '5px 20px', fontSize: '1em', borderRadius: 4, background: '#f3f3f6', border: '1px solid #bbb', color: '#222', cursor: 'pointer' }}
						onClick={() => handleLoadMore('plugins', plugins.length)}
					>
						{__('Load more', 'wp-org-portfolio-block-wp')}
					</WPButton>
				</div>
			)}
			</div>
		);
	}

	function renderThemes() {
		if (!themes.length) return <div>{__('No themes found.', 'wp-org-portfolio-block-wp')}</div>;
		const toShow = Number(loadedCount) || perPage;
		const showThemes = themes.slice(0, toShow);
		return (
			<div className="was-grid">
			{showThemes.map((theme) => {
				const screenshot = theme.screenshot_url || '';
				const name = decodeAllEntities(theme.name);
				let desc = decodeAllEntities(theme.description);
				if (desc && desc.length > 100) desc = desc.substring(0, 100) + '...';
				const learnMoreUrl = `https://wordpress.org/themes/${theme.slug}/`;
				return (
					<div key={theme.slug} className="was-card">
						{screenshot && <img src={screenshot} alt={name} />}
						<div className="was-card-content">
							<a href={learnMoreUrl} target="_blank" rel="noopener noreferrer">{name}</a>
							{desc && <p>{desc}</p>}
							<WPButton
								onClick={() => window.open(learnMoreUrl, '_blank')}
								className="was-learn-more"
								tabIndex={0}
							>
								{__('Learn more', 'wp-org-portfolio-block-wp')}
							</WPButton>
						</div>
					</div>
				);
			})}
			{showThemes.length < themes.length && (
				<div style={{ display: 'flex', justifyContent: 'center' }}>
					<WPButton
						className="was-load-more-btn"
						style={{ margin: '0 0 8px 0', padding: '5px 20px', fontSize: '1em', borderRadius: 4, background: '#f3f3f6', border: '1px solid #bbb', color: '#222', cursor: 'pointer' }}
						onClick={() => handleLoadMore('themes', themes.length)}
					>
						{__('Load more', 'wp-org-portfolio-block-wp')}
					</WPButton>
				</div>
			)}
			</div>
		);
	}

	const renderContent = () => {
		if (!username) {
			return <div style={{ marginTop: '22px', fontSize: '1.08rem', color: '#4a4a4a', display: 'flex', alignItems: 'center', justifyContent: 'center', textAlign: 'center', minHeight: '44px' }}>{__("Let's see what magic you've created! Add your WordPress.org username to get started.", 'wp-org-portfolio-block-wp')}</div>;
		}
		if (loading) {
			return <Spinner />;
		}
		if (error) {
			return <Notice status="error">{error}</Notice>;
		}
		return (
			<div>
				{showType === 'plugins' && (
					<div>
						<strong>{__('Plugins', 'wp-org-portfolio-block-wp')}</strong>
						{renderPlugins()}
					</div>
				)}
				{showType === 'themes' && (
					<div>
						<strong>{__('Themes', 'wp-org-portfolio-block-wp')}</strong>
						{renderThemes()}
					</div>
				)}
			</div>
		);
	};

	return (
		<div {...blockProps}>
			<BlockControls>
				<AlignmentControl
					value={align}
					onChange={(newAlign) => setAttributes({ align: newAlign })}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={__('WP.org Author Settings', 'wp-org-portfolio-block-wp')} initialOpen={true}>
					<TextControl
						label={__('Username', 'wp-org-portfolio-block-wp')}
						value={username}
						onChange={(v) => setAttributes({ username: v, loadedCount: undefined })}
						placeholder={__('e.g. automattic', 'wp-org-portfolio-block-wp')}
					/>
					<SelectControl
						label={__('Show', 'wp-org-portfolio-block-wp')}
						value={showType}
						options={[
							{ label: __('Plugins', 'wp-org-portfolio-block-wp'), value: 'plugins' },
							{ label: __('Themes', 'wp-org-portfolio-block-wp'), value: 'themes' }
						]}
						onChange={(v) => setAttributes({ showType: v, loadedCount: undefined })}
					/>
					<TextControl
						label={__('Items per click', 'wp-org-portfolio-block-wp')}
						type='number'
						min='1'
						max='50'
						value={perPage}
						onChange={(v) => setAttributes({ perPage: Math.max(1, Math.min(50, parseInt(v) || 1)), loadedCount: undefined })}
					/>
				</PanelBody>
				<PoweredByTelexPanel />
			</InspectorControls>
			<div>
				{renderContent()}
			</div>
		</div>
	);
}
