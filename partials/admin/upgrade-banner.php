<?php
/**
 * Pro upgrade banner — shown for Pro-only pages in the free plugin.
 *
 * @package WPBuoy_Endpoint_Manager
 * @var string $heading      Page-specific heading text.
 * @var string $description  Page-specific description text.
 * @var array  $features     List of Pro feature strings.
 * @var string $cta_url      URL for the primary CTA button.
 * @var string $cta_label    Optional. Primary CTA button label. Defaults to 'Upgrade to Pro'.
 * @var bool   $cta_external Optional. Whether the CTA opens in a new tab. Defaults to true.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="wpbyem-upgrade-banner">
		<div class="wpbyem-upgrade-banner__wrap">
			<div class="wpbyem-upgrade-banner__grid">
				<div class="wpbyem-upgrade-value-prop">
					<?php if ( ! empty( $heading ) ) : ?>
					<h2 class="wpbyem-upgrade-banner__heading">
						<?php echo esc_html( $heading ); ?>
					</h2>
					<?php endif; ?>
					<?php if ( ! empty( $description ) ) : ?>
					<p class="wpbyem-upgrade-banner__desc">
						<?php echo esc_html( $description ); ?>
					</p>
					<?php endif; ?>

					<div class="wpbyem-upgrade-banner__ctas">
						<a class="button" href="https://wpbuoy.com/endpoint-manager/knowledge-base/" rel="noopener noreferrer" target="_blank">Learn More</a>
						<a
							class="button button-primary"
							href="<?php echo esc_url( $cta_url ); ?>"
							<?php if ( ! isset( $cta_external ) || $cta_external ) : ?>rel="noopener noreferrer" target="_blank"<?php endif; ?>
						><?php echo esc_html( isset( $cta_label ) ? $cta_label : __( 'Upgrade to Pro', 'wpbuoy-endpoint-manager' ) ); ?></a>
					</div>
				</div>
				<div class="wpbyem-upgrade-features">
					<ul class="wpbyem-upgrade-banner__features">
						<?php foreach ( $features as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
		
			<div class="wpbyem-upgrade-banner__footer">
				<div class="wpbyem-upgrade-banner__wrap">
					<a href="https://wpbuoy.com" rel="noopener noreferrer" target="_blank" aria-label="<?php esc_attr_e( 'WPBuoy', 'wpbuoy-endpoint-manager' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="87" height="24" aria-hidden="true" focusable="false"><path fill="#fff" fill-rule="evenodd" d="m6.083 13.477 1.943-6.613h3.795l1.875 6.692 1.94-6.692h3.914l-3.756 12.517h-4.349l-1.634-5.793-1.706 5.793h-4.35L0 6.864h4.23zm71.695 5.953L72.607 6.863h4.351l2.954 7.582 2.987-7.582h4.026l-5.21 12.924q-.628 1.594-1.438 2.496-.81.9-1.8 1.264a6.3 6.3 0 0 1-2.179.365 6.5 6.5 0 0 1-1.934-.302q-.973-.303-1.583-.82l1.47-3.028q.393.333.892.53.5.198.986.197.696 0 1.094-.321.312-.252.555-.738m-53.696-1.244v5.525h-4.23V6.863h4.027v1.236q.373-.45.856-.754 1.084-.683 2.699-.683 1.65 0 2.994.786t2.138 2.22q.795 1.437.795 3.407 0 1.972-.795 3.406-.794 1.436-2.138 2.214-1.343.777-2.994.778-1.54 0-2.631-.691a3.7 3.7 0 0 1-.72-.596m2.46-2.173q.715 0 1.285-.342t.91-1.004.34-1.592q0-.951-.34-1.61a2.43 2.43 0 0 0-.91-1.002 2.45 2.45 0 0 0-1.285-.342 2.45 2.45 0 0 0-1.285.342q-.57.342-.91 1.002t-.34 1.61q0 .93.34 1.592t.91 1.004q.57.342 1.285.342m8.034.783L31.19.866l4.14-.88 1.9 8.943q.247-.405.581-.732.924-.902 2.43-1.222 1.615-.344 3.09.138 1.477.481 2.552 1.72Q46.96 10.073 47.37 12t-.07 3.497q-.113.375-.266.718a62 62 0 0 0-4.834-.273q.159-.106.3-.237.485-.453.68-1.18.192-.726-.001-1.634-.198-.931-.666-1.498a2.44 2.44 0 0 0-1.096-.782 2.45 2.45 0 0 0-1.328-.067q-.7.149-1.186.601a2.44 2.44 0 0 0-.684 1.161q-.195.708.001 1.639.193.908.665 1.493.274.34.6.555-2.916.162-4.908.803m13.611-.332q-.414-1.114-.414-2.67V6.863h4.231v6.223q0 1.44.534 2.067.534.625 1.483.625.632 0 1.142-.3t.815-.954.303-1.674V6.863h4.232v11.381a76 76 0 0 1-5.66-.786q-3.582-.625-6.666-.994m15.52 2.019a6.2 6.2 0 0 1-2.126-2.108q-.882-1.447-.882-3.317 0-1.877.883-3.32a6.2 6.2 0 0 1 2.416-2.26q1.532-.816 3.52-.816 1.989 0 3.532.816a6.1 6.1 0 0 1 2.416 2.258q.87 1.444.871 3.322 0 1.564-.607 2.83-3.066 2.606-10.023 2.595m3.811-2.47q.723 0 1.293-.342t.906-1.012.336-1.6q0-.95-.336-1.603a2.4 2.4 0 0 0-.906-.993 2.46 2.46 0 0 0-1.29-.342q-.718 0-1.288.342-.57.343-.91.993-.34.652-.34 1.602 0 .93.34 1.6.34.672.91 1.013.569.342 1.285.342"/></svg></a>
				</div>
			</div>
	</div>
</div>
