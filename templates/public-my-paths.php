<?php
/**
 * Public My Paths template.
 *
 * @package QuestUno
 */

defined( 'ABSPATH' ) || exit;

$questuno_context = get_query_var( 'questuno_public_my_paths_context', array() );
$questuno_context = is_array( $questuno_context ) ? $questuno_context : array();

$questuno_page_title = isset( $questuno_context['page_title'] ) ? (string) $questuno_context['page_title'] : __( 'My paths', 'questuno' );
$questuno_items      = isset( $questuno_context['items'] ) && is_array( $questuno_context['items'] ) ? $questuno_context['items'] : array();

get_header();
?>
<main id="primary" class="site-main questuno-public-my-paths">
	<div class="questuno-public-my-paths__content">
		<h1><?php echo esc_html( $questuno_page_title ); ?></h1>

		<?php if ( empty( $questuno_items ) ) : ?>
			<p><?php esc_html_e( 'You do not have any active paths yet.', 'questuno' ); ?></p>
		<?php else : ?>
			<div class="questuno-public-my-paths__list">
				<?php foreach ( $questuno_items as $questuno_item ) : ?>
					<article class="questuno-public-my-paths__item">
						<h2><?php echo esc_html( isset( $questuno_item['path_name'] ) ? (string) $questuno_item['path_name'] : __( 'Path', 'questuno' ) ); ?></h2>
						<p>
							<strong><?php esc_html_e( 'Status:', 'questuno' ); ?></strong>
							<?php echo esc_html( isset( $questuno_item['status'] ) ? (string) $questuno_item['status'] : '' ); ?>
						</p>
						<p>
							<strong><?php esc_html_e( 'Progress:', 'questuno' ); ?></strong>
							<?php echo esc_html( isset( $questuno_item['progress_label'] ) ? (string) $questuno_item['progress_label'] : '' ); ?>
						</p>
						<?php if ( ! empty( $questuno_item['action_url'] ) ) : ?>
							<p>
								<a class="questuno-public-my-paths__action" href="<?php echo esc_url( (string) $questuno_item['action_url'] ); ?>">
									<?php esc_html_e( 'Open path', 'questuno' ); ?>
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
