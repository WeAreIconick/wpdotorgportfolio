<?php
if ( ! function_exists( 'was_decode_all_entities' ) ) {
	function was_decode_all_entities( $value ) {
		if ( ! is_string( $value ) || $value === '' ) {
			return '';
		}
		for ( $i = 0; $i < 12; $i++ ) {
			$prev = $value;
			$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$value = htmlspecialchars_decode( $value, ENT_QUOTES | ENT_HTML5 );
			if ( $value === $prev ) {
				break;
			}
		}
		$value = preg_replace( '/&[#A-Za-z0-9]+;/', '', $value );
		$value = preg_replace( '/[\x{2012}-\x{2015}\x{2212}\x{2010}-\x{2014}]/u', '-', $value );
		return $value;
	}
}
if ( ! function_exists( 'was_sanitize_output_text' ) ) {
	function was_sanitize_output_text( $text ) {
		$out = (string) $text;
		try {
			$out = was_decode_all_entities($out);
		} catch (Exception $e) {
			$out = $text;
		}
		$out = wp_strip_all_tags( $out );
		$out = preg_replace('/&[a-zA-Z0-9#]+;/', '', $out );
		return $out;
	}
}
if ( ! function_exists( 'wporg_author_showcase_render_block' ) ) {
function wporg_author_showcase_render_block( $attributes ) {
	$username = isset( $attributes['username'] ) ? sanitize_text_field( $attributes['username'] ) : '';
	$show_type = isset( $attributes['showType'] ) ? $attributes['showType'] : 'plugins';
	$per_page = isset($attributes['perPage']) ? intval($attributes['perPage']) : 10;
	$align = !empty($attributes['align']) ? ' align' . esc_attr($attributes['align']) : '';
	$loaded_count = isset($attributes['loadedCount']) ? intval($attributes['loadedCount']) : $per_page;

	if ( ! $username ) {
		return '<div style="margin-top:22px;font-size:1.08rem;color:#4a4a4a">' . esc_html__( "Let's see what magic you've created! Add your WordPress.org username to get started.", 'wp-org-portfolio-block-wp' ) . '</div>';
	}
	$api_route = '/wporg-showcase/v1/author/' . rawurlencode( $username );
	$request = new WP_REST_Request( 'GET', $api_route );
	$response = rest_do_request( $request );
	$data = $response->is_error() ? array() : $response->get_data();

	$plugins = ! empty( $data['plugins'] ) && is_array( $data['plugins'] ) ? $data['plugins'] : array();
	$themes  = ! empty( $data['themes'] ) && is_array( $data['themes'] ) ? $data['themes'] : array();

	ob_start();
	?>
	<div class="wp-block-w0-wp-org-portfolio<?php echo $align; ?>" data-username="<?php echo esc_attr( $username ); ?>" data-showtype="<?php echo esc_attr( $show_type ); ?>" data-per-page="<?php echo esc_attr($per_page); ?>" data-loaded-count="<?php echo esc_attr($loaded_count); ?>">
		<?php if ( $show_type === 'plugins' ) : ?>
		<div>
			<strong><?php esc_html_e( 'Plugins', 'wp-org-portfolio-block-wp' ); ?></strong>
			<div class="was-grid">
				<?php
				$to_show = array_slice($plugins, 0, $loaded_count);
				if ( ! empty( $to_show ) ) {
					foreach ($to_show as $plugin ) : ?>
					<div class="was-card" style="position:relative;">
						<?php
						$icon_url = '';
						if ( ! empty( $plugin['icons']['1x'] ) ) {
							$icon_url = $plugin['icons']['1x'];
						} elseif ( ! empty( $plugin['icons']['default'] ) ) {
							$icon_url = $plugin['icons']['default'];
						}
						if ( $icon_url ) {
							echo '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( was_sanitize_output_text( $plugin['name'] ) ) . '" />';
						}
						?>
						<div style="flex:1;position:relative;min-height:80px;">
							<a href="<?php echo esc_url( 'https://wordpress.org/plugins/' . $plugin['slug'] . '/' ); ?>" target="_blank" rel="noopener noreferrer" style="font-weight:bold;font-size:1.2rem;color:#262626;text-decoration:none;">
								<?php echo was_sanitize_output_text( $plugin['name'] ); ?>
							</a>
							<?php 
							$rating = isset( $plugin['rating'] ) ? floatval( $plugin['rating'] ) : null;
							$num_ratings = isset( $plugin['num_ratings'] ) ? intval( $plugin['num_ratings'] ) : null;
							$avg_rating = ( $rating !== null && $num_ratings ) ? $rating / 20 : null;
							if ( $avg_rating ) : ?>
								<div style="margin:0.2rem 0 0 0;display:flex;align-items:center;gap:8px;">
									<span style="color:#ffc844;font-size:1.08rem;letter-spacing:0.05em;" aria-label="<?php esc_attr_e('Star rating','wp-org-portfolio-block-wp'); ?>">
									<?php
									for ( $i = 1; $i <= 5; $i++ ) {
										if ( $avg_rating >= $i ) {
											echo '★';
										} elseif ( $avg_rating >= ($i - 0.5) ) {
											echo '☆';
										} else {
											echo '☆';
										}
									}
									?>
									</span>
									<span style="font-size:0.95rem;color:#6e6e6e;">
										<?php echo esc_html( number_format_i18n( $avg_rating, 1 ) ); ?> / 5
									</span>
									<span style="font-size:0.93rem;color:#aaa;">(<?php echo esc_html( $num_ratings ); ?> ratings)</span>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $plugin['short_description'] ) ) : ?>
								<p style="margin:0.5rem 0 0.25rem 0;font-size:0.98rem;color:#555;">
									<?php echo was_sanitize_output_text( $plugin['short_description'] ); ?>
								</p>
							<?php endif; ?>
							<?php if ( ! empty( $plugin['active_installs'] ) ) : ?>
								<div style="font-size:0.95rem;color:#999;margin-top:0.5rem;">
									<?php echo esc_html( number_format_i18n( $plugin['active_installs'] ) ) . '+'; ?> installs
								</div>
							<?php endif; ?>
							<button onclick="window.open('<?php echo esc_url( 'https://wordpress.org/plugins/' . $plugin['slug'] . '/' ); ?>','_blank')" type="button" class="was-learn-more" style="position:absolute;right:22px;bottom:20px;font-size:0.98rem;color:#2271b1;text-decoration:underline;font-weight:500;padding:5px 20px;z-index:10;">
								<?php esc_html_e('Learn more', 'wp-org-portfolio-block-wp'); ?>
							</button>
						</div>
					</div>
				<?php endforeach; } else {
					echo '<div>' . esc_html__( 'No plugins found.', 'wp-org-portfolio-block-wp' ) . '</div>';
				} ?>
			</div>
			<?php if ($loaded_count < count($plugins)) : ?>
				<button class="was-load-more-btn" data-jsattr="plugins" style="margin:0 auto 8px auto;display:block;padding:5px 20px;font-size:1em;border-radius:4px;background:#f3f3f6;border:1px solid #bbb;color:#222;cursor:pointer;">
					<?php esc_html_e('Load more', 'wp-org-portfolio-block-wp'); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<?php if ( $show_type === 'themes' ) : ?>
		<div>
			<strong><?php esc_html_e( 'Themes', 'wp-org-portfolio-block-wp' ); ?></strong>
			<div class="was-grid">
				<?php
				$to_show = array_slice($themes, 0, $loaded_count);
				if ( ! empty( $to_show ) ) {
					foreach ($to_show as $theme ) : ?>
					<div class="was-card" style="position:relative;">
						<?php if ( ! empty( $theme['screenshot_url'] ) ) : ?>
							<img src="<?php echo esc_url( $theme['screenshot_url'] ); ?>" alt="<?php echo esc_attr( was_sanitize_output_text( $theme['name'] ) ); ?>" />
						<?php endif; ?>
						<div style="flex:1;position:relative;min-height:80px;">
							<a href="<?php echo esc_url( 'https://wordpress.org/themes/' . $theme['slug'] . '/' ); ?>" target="_blank" rel="noopener noreferrer" style="font-weight:bold;font-size:1.2rem;color:#262626;text-decoration:none;">
								<?php echo was_sanitize_output_text( $theme['name'] ); ?>
							</a>
							<?php if ( ! empty( $theme['description'] ) ) : ?>
								<p style="margin:0.5rem 0 0.25rem 0;font-size:0.98rem;color:#555;">
									<?php
									$desc = (string) $theme['description'];
									$desc = mb_strlen( $desc ) > 100 ? mb_substr( $desc, 0, 100 ) . '...' : $desc;
									echo was_sanitize_output_text( $desc );
								?>
								</p>
							<?php endif; ?>
							<button onclick="window.open('<?php echo esc_url( 'https://wordpress.org/themes/' . $theme['slug'] . '/' ); ?>','_blank')" type="button" class="was-learn-more" style="position:absolute;right:22px;bottom:20px;font-size:0.98rem;color:#2271b1;text-decoration:underline;font-weight:500;padding:5px 20px;z-index:10;">
								<?php esc_html_e('Learn more', 'wp-org-portfolio-block-wp'); ?>
							</button>
						</div>
					</div>
				<?php endforeach; } else {
					echo '<div>' . esc_html__( 'No themes found.', 'wp-org-portfolio-block-wp' ) . '</div>';
				} ?>
			</div>
			<?php if ($loaded_count < count($themes)) : ?>
				<button class="was-load-more-btn" data-jsattr="themes" style="margin:0 auto 8px auto;display:block;padding:5px 20px;font-size:1em;border-radius:4px;background:#f3f3f6;border:1px solid #bbb;color:#222;cursor:pointer;">
					<?php esc_html_e('Load more', 'wp-org-portfolio-block-wp'); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
}
