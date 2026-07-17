<?php
/**
 * Public My Paths template.
 *
 * @package QRHunt
 */

defined( 'ABSPATH' ) || exit;

$qrhunt_context = get_query_var( 'qrhunt_public_my_paths_context', array() );
$qrhunt_context = is_array( $qrhunt_context ) ? $qrhunt_context : array();

$qrhunt_page_title = isset( $qrhunt_context['page_title'] ) ? (string) $qrhunt_context['page_title'] : __( 'My paths', 'qrhunt' );
$qrhunt_items      = isset( $qrhunt_context['items'] ) && is_array( $qrhunt_context['items'] ) ? $qrhunt_context['items'] : array();

get_header();
?>
<main id="primary" class="site-main qrhunt-public-my-paths">
	<div class="qrhunt-public-my-paths__content">
		<h1><?php echo esc_html( $qrhunt_page_title ); ?></h1>

		<?php if ( empty( $qrhunt_items ) ) : ?>
			<p><?php esc_html_e( 'You do not have any active paths yet.', 'qrhunt' ); ?></p>
		<?php else : ?>
			<div class="qrhunt-public-my-paths__list">
				<?php foreach ( $qrhunt_items as $qrhunt_item ) : ?>
					<article class="qrhunt-public-my-paths__item">
						<h2><?php echo esc_html( isset( $qrhunt_item['path_name'] ) ? (string) $qrhunt_item['path_name'] : __( 'Path', 'qrhunt' ) ); ?></h2>
						<p>
							<strong><?php esc_html_e( 'Status:', 'qrhunt' ); ?></strong>
							<?php echo esc_html( isset( $qrhunt_item['status'] ) ? (string) $qrhunt_item['status'] : '' ); ?>
						</p>
						<p>
							<strong><?php esc_html_e( 'Progress:', 'qrhunt' ); ?></strong>
							<?php echo esc_html( isset( $qrhunt_item['progress_label'] ) ? (string) $qrhunt_item['progress_label'] : '' ); ?>
						</p>
						<?php if ( ! empty( $qrhunt_item['action_url'] ) ) : ?>
							<p>
								<a class="qrhunt-public-my-paths__action" href="<?php echo esc_url( (string) $qrhunt_item['action_url'] ); ?>">
									<?php esc_html_e( 'Open path', 'qrhunt' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
