<?php
/**
 * Public Checkpoint template.
 *
 * @package QRHunt
 */

defined( 'ABSPATH' ) || exit;

$qrhunt_context = get_query_var( 'qrhunt_public_ui_context', array() );
$qrhunt_context = is_array( $qrhunt_context ) ? $qrhunt_context : array();

$qrhunt_page_title         = isset( $qrhunt_context['page_title'] ) ? (string) $qrhunt_context['page_title'] : __( 'Checkpoint', 'qrhunt' );
$qrhunt_validation_outcome = isset( $qrhunt_context['validation_outcome'] ) ? (string) $qrhunt_context['validation_outcome'] : '';
$qrhunt_message            = isset( $qrhunt_context['message'] ) ? (string) $qrhunt_context['message'] : '';
$qrhunt_participation      = $qrhunt_context['participation'] ?? null;
$qrhunt_violation_messages = isset( $qrhunt_context['violation_messages'] ) && is_array( $qrhunt_context['violation_messages'] ) ? $qrhunt_context['violation_messages'] : array();
$qrhunt_render_content     = ! empty( $qrhunt_context['render_content'] );
$qrhunt_banner_message     = isset( $qrhunt_context['banner_message'] ) ? (string) $qrhunt_context['banner_message'] : '';
$qrhunt_banner_modifier    = isset( $qrhunt_context['banner_modifier'] ) ? (string) $qrhunt_context['banner_modifier'] : '';
$qrhunt_path_name          = isset( $qrhunt_context['path_name'] ) ? (string) $qrhunt_context['path_name'] : '';
$qrhunt_progress_label     = isset( $qrhunt_context['progress_label'] ) ? (string) $qrhunt_context['progress_label'] : '';
$qrhunt_my_paths_url       = isset( $qrhunt_context['my_paths_url'] ) ? (string) $qrhunt_context['my_paths_url'] : '';
$qrhunt_render_navigation  = ! empty( $qrhunt_context['render_navigation'] );
$qrhunt_checkpoint_content = isset( $qrhunt_context['checkpoint_content'] ) ? (string) $qrhunt_context['checkpoint_content'] : '';

get_header();
?>
<main id="primary" class="site-main qrhunt-public-checkpoint">
	<div class="qrhunt-public-checkpoint__content">
		<h1><?php echo esc_html( $qrhunt_page_title ); ?></h1>

		<?php if ( '' !== $qrhunt_banner_message ) : ?>
			<div class="qrhunt-public-ui__notice qrhunt-public-ui__notice--<?php echo esc_attr( $qrhunt_banner_modifier ); ?>">
				<p><?php echo esc_html( $qrhunt_banner_message ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $qrhunt_progress_label ) : ?>
			<section class="qrhunt-public-ui__progress" aria-label="<?php esc_attr_e( 'Path progress', 'qrhunt' ); ?>">
				<p class="qrhunt-public-ui__progress-title"><?php echo esc_html( $qrhunt_path_name ); ?></p>
				<p class="qrhunt-public-ui__progress-value"><?php echo esc_html( $qrhunt_progress_label ); ?></p>
			</section>
		<?php endif; ?>

		<?php if ( '' !== $qrhunt_validation_outcome ) : ?>
			<p><strong><?php esc_html_e( 'Validation outcome:', 'qrhunt' ); ?></strong> <?php echo esc_html( $qrhunt_validation_outcome ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $qrhunt_message ) : ?>
			<p><?php echo esc_html( $qrhunt_message ); ?></p>
		<?php endif; ?>

		<p>
			<strong><?php esc_html_e( 'Participation status:', 'qrhunt' ); ?></strong>
			<?php echo esc_html( null === $qrhunt_participation ? __( 'Not started', 'qrhunt' ) : (string) $qrhunt_participation->get_status() ); ?>
		</p>

		<?php if ( ! empty( $qrhunt_violation_messages ) ) : ?>
			<section class="qrhunt-public-checkpoint__violations">
				<h2><?php esc_html_e( 'Validation details', 'qrhunt' ); ?></h2>
				<ul>
					<?php foreach ( $qrhunt_violation_messages as $qrhunt_violation_message ) : ?>
						<li><?php echo esc_html( (string) $qrhunt_violation_message ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $qrhunt_render_content && '' !== $qrhunt_checkpoint_content ) : ?>
			<section class="qrhunt-public-checkpoint__body">
				<?php echo wp_kses_post( $qrhunt_checkpoint_content ); ?>
			</section>
		<?php endif; ?>

		<?php if ( $qrhunt_render_navigation && '' !== $qrhunt_my_paths_url ) : ?>
			<nav class="qrhunt-public-ui__navigation" aria-label="<?php esc_attr_e( 'Player navigation', 'qrhunt' ); ?>">
				<a class="qrhunt-public-ui__my-paths-link" href="<?php echo esc_url( $qrhunt_my_paths_url ); ?>">
					<?php esc_html_e( 'My paths', 'qrhunt' ); ?>
				</a>
			</nav>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
