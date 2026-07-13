<?php
/**
 * Dependency controller.
 *
 * @package QRHunt
 */

namespace QRHunt\Controller;

use QRHunt\Model\Dependency;
use QRHunt\Service\CheckpointService;
use QRHunt\Service\DependencyService;
use QRHunt\Service\GroupService;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates Dependency rendering and persistence.
 */
final class DependencyController {

	/** @var DependencyService */
	private $dependency_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var GroupService */
	private $group_service;

	/**
	 * Creates a Dependency controller.
	 *
	 * @param DependencyService $dependency_service Dependency service.
	 * @param CheckpointService $checkpoint_service Checkpoint service.
	 * @param GroupService      $group_service      Group service.
	 */
	public function __construct( DependencyService $dependency_service, CheckpointService $checkpoint_service, GroupService $group_service ) {
		$this->dependency_service = $dependency_service;
		$this->checkpoint_service = $checkpoint_service;
		$this->group_service      = $group_service;
	}

	/**
	 * Renders the Dependency section inside the Checkpoint metabox.
	 *
	 * @param \WP_Post $post    Current post.
	 * @param int      $path_id Selected path identifier.
	 * @return void
	 */
	public function render_section( \WP_Post $post, int $path_id ): void {
		$current_checkpoint_id     = (int) $post->ID;
		$dependencies             = $this->dependency_service->get_dependencies_by_checkpoint( $post->ID );
		$checkpoints              = $this->checkpoint_service->get_checkpoints();
		$groups                   = $this->group_service->get_groups();
		$checkpoints_by_path      = $this->checkpoint_service->get_checkpoints_by_path( $path_id );
		$groups_by_path           = $this->group_service->get_groups_by_path( $path_id );
		$available_checkpoint_ids = array();
		$available_group_ids      = array();
		$checkpoint_titles        = $this->get_checkpoint_titles( $checkpoints );

		foreach ( $checkpoints_by_path as $checkpoint ) {
			if ( $current_checkpoint_id === $checkpoint->get_post_id() ) {
				continue;
			}

			$available_checkpoint_ids[ $checkpoint->get_post_id() ] = true;
		}

		foreach ( $groups_by_path as $group ) {
			$available_group_ids[ $group->get_id() ] = true;
		}
		?>
		<div class="qrhunt-dependencies">
			<p><strong><?php esc_html_e( 'Dependencies', 'qrhunt' ); ?></strong></p>
			<div id="qrhunt-dependency-rows">
				<?php foreach ( $dependencies as $index => $dependency ) : ?>
					<?php $this->render_row( $index, $dependency, $checkpoints, $groups, $available_checkpoint_ids, $available_group_ids, $checkpoint_titles, $current_checkpoint_id ); ?>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button" id="qrhunt-add-dependency"><?php esc_html_e( 'Add Dependency', 'qrhunt' ); ?></button>
			</p>
		</div>
		<script type="text/html" id="tmpl-qrhunt-dependency-row">
			<?php $this->render_row( '__index__', null, $checkpoints, $groups, $available_checkpoint_ids, $available_group_ids, $checkpoint_titles, $current_checkpoint_id ); ?>
		</script>
		<script>
			(function() {
				const pathField = document.getElementById( 'qrhunt-path-id' );
				const dependencyRows = document.getElementById( 'qrhunt-dependency-rows' );
				const addDependencyButton = document.getElementById( 'qrhunt-add-dependency' );
				const dependencyTemplate = document.getElementById( 'tmpl-qrhunt-dependency-row' );

				if ( ! pathField || ! dependencyRows || ! addDependencyButton || ! dependencyTemplate ) {
					return;
				}

				const syncRow = function(row) {
					const targetTypeField = row.querySelector( '.qrhunt-dependency-target-type' );
					const checkpointSelect = row.querySelector( '.qrhunt-dependency-checkpoint' );
					const groupSelect = row.querySelector( '.qrhunt-dependency-group' );
					const selectedPathId = pathField.value;
					let hasSelectedCheckpoint = false;
					let hasSelectedGroup = false;

					Array.from( checkpointSelect.options ).forEach( function( option, index ) {
						if ( 0 === index ) {
							return;
						}

						const matchesPath = option.dataset.pathId === selectedPathId;

						option.hidden = ! matchesPath;
						option.disabled = ! matchesPath;

						if ( matchesPath && option.selected ) {
							hasSelectedCheckpoint = true;
						}
					} );

					if ( ! hasSelectedCheckpoint ) {
						checkpointSelect.value = '0';
					}

					Array.from( groupSelect.options ).forEach( function( option, index ) {
						if ( 0 === index ) {
							return;
						}

						const matchesPath = option.dataset.pathId === selectedPathId;

						option.hidden = ! matchesPath;
						option.disabled = ! matchesPath;

						if ( matchesPath && option.selected ) {
							hasSelectedGroup = true;
						}
					} );

					if ( ! hasSelectedGroup ) {
						groupSelect.value = '0';
					}

					checkpointSelect.style.display = 'checkpoint' === targetTypeField.value ? '' : 'none';
					groupSelect.style.display = 'group' === targetTypeField.value ? '' : 'none';
				};

				const bindRow = function(row) {
					const targetTypeField = row.querySelector( '.qrhunt-dependency-target-type' );
					const removeButton = row.querySelector( '.qrhunt-remove-dependency' );

					targetTypeField.addEventListener( 'change', function() {
						syncRow( row );
					} );

					removeButton.addEventListener( 'click', function() {
						row.remove();
					} );

					syncRow( row );
				};

				const syncRows = function() {
					Array.from( dependencyRows.querySelectorAll( '.qrhunt-dependency-row' ) ).forEach( function(row) {
						syncRow( row );
					} );
				};

				Array.from( dependencyRows.querySelectorAll( '.qrhunt-dependency-row' ) ).forEach( function(row) {
					bindRow( row );
				} );

				addDependencyButton.addEventListener( 'click', function() {
					const index = dependencyRows.querySelectorAll( '.qrhunt-dependency-row' ).length;
					const html = dependencyTemplate.innerHTML.replaceAll( '__index__', index );

					dependencyRows.insertAdjacentHTML( 'beforeend', html );
					bindRow( dependencyRows.lastElementChild );
				} );

				pathField.addEventListener( 'change', syncRows );
				syncRows();
			}() );
		</script>
		<?php
	}

	/**
	 * Saves the Dependencies submitted by the Checkpoint form.
	 *
	 * @param int $checkpoint_id Checkpoint identifier.
	 * @return void
	 */
	public function save( int $checkpoint_id ): void {
		$this->dependency_service->save_dependencies( $checkpoint_id, $this->parse_request( $checkpoint_id ) );
	}

	/**
	 * Renders a single Dependency row.
	 *
	 * @param int|string                 $index                    Row index.
	 * @param Dependency|null            $dependency               Dependency model.
	 * @param array<int, \QRHunt\Model\Checkpoint> $checkpoints   Available checkpoints.
	 * @param array<int, \QRHunt\Model\Group>      $groups        Available groups.
	 * @param array<int, bool>           $available_checkpoint_ids Checkpoints available for the selected path.
	 * @param array<int, bool>           $available_group_ids      Groups available for the selected path.
	 * @param array<int, string>         $checkpoint_titles        Checkpoint titles indexed by post ID.
	 * @param int                        $current_checkpoint_id    Current Checkpoint identifier.
	 * @return void
	 */
	private function render_row( $index, ?Dependency $dependency, array $checkpoints, array $groups, array $available_checkpoint_ids, array $available_group_ids, array $checkpoint_titles, int $current_checkpoint_id ): void {
		$type        = null === $dependency || null === $dependency->get_type() ? 'after' : $dependency->get_type();
		$target_type = null === $dependency || null === $dependency->get_target_type() ? 'checkpoint' : $dependency->get_target_type();
		$target_id   = null === $dependency || null === $dependency->get_target_id() ? 0 : (int) $dependency->get_target_id();
		?>
		<div class="qrhunt-dependency-row" style="margin-bottom:12px;">
			<select name="qrhunt_dependency_type[<?php echo esc_attr( (string) $index ); ?>]">
				<option value="before" <?php selected( $type, 'before' ); ?>><?php esc_html_e( 'BEFORE', 'qrhunt' ); ?></option>
				<option value="after" <?php selected( $type, 'after' ); ?>><?php esc_html_e( 'AFTER', 'qrhunt' ); ?></option>
			</select>
			<select name="qrhunt_dependency_target_type[<?php echo esc_attr( (string) $index ); ?>]" class="qrhunt-dependency-target-type">
				<option value="checkpoint" <?php selected( $target_type, 'checkpoint' ); ?>><?php esc_html_e( 'Checkpoint', 'qrhunt' ); ?></option>
				<option value="group" <?php selected( $target_type, 'group' ); ?>><?php esc_html_e( 'Group', 'qrhunt' ); ?></option>
			</select>
			<select name="qrhunt_dependency_checkpoint_id[<?php echo esc_attr( (string) $index ); ?>]" class="qrhunt-dependency-checkpoint">
				<option value="0"><?php esc_html_e( 'Select a Checkpoint', 'qrhunt' ); ?></option>
				<?php foreach ( $checkpoints as $checkpoint ) : ?>
					<?php $checkpoint_id = $checkpoint->get_post_id(); ?>
					<?php if ( $current_checkpoint_id === $checkpoint_id ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php $is_available  = isset( $available_checkpoint_ids[ $checkpoint_id ] ); ?>
					<option
						value="<?php echo esc_attr( (string) $checkpoint_id ); ?>"
						data-path-id="<?php echo esc_attr( (string) $checkpoint->get_path_id() ); ?>"
						<?php selected( 'checkpoint' === $target_type ? $target_id : 0, $checkpoint_id ); ?>
						<?php disabled( ! $is_available ); ?>
						<?php echo $is_available ? '' : 'hidden'; ?>
					>
						<?php echo esc_html( $checkpoint_titles[ $checkpoint_id ] ?? (string) $checkpoint_id ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="qrhunt_dependency_group_id[<?php echo esc_attr( (string) $index ); ?>]" class="qrhunt-dependency-group">
				<option value="0"><?php esc_html_e( 'Select a Group', 'qrhunt' ); ?></option>
				<?php foreach ( $groups as $group ) : ?>
					<?php $is_available = isset( $available_group_ids[ $group->get_id() ] ); ?>
					<option
						value="<?php echo esc_attr( (string) $group->get_id() ); ?>"
						data-path-id="<?php echo esc_attr( (string) $group->get_path_id() ); ?>"
						<?php selected( 'group' === $target_type ? $target_id : 0, $group->get_id() ); ?>
						<?php disabled( ! $is_available ); ?>
						<?php echo $is_available ? '' : 'hidden'; ?>
					>
						<?php echo esc_html( $group->get_name() ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button-link-delete qrhunt-remove-dependency"><?php esc_html_e( 'Delete', 'qrhunt' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Parses Dependency rows from the request.
	 *
	 * @param int $checkpoint_id Checkpoint identifier.
	 * @return array<int, Dependency>
	 */
	private function parse_request( int $checkpoint_id ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in CheckpointController::save() before delegating the Dependency parsing.
		$types          = isset( $_POST['qrhunt_dependency_type'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['qrhunt_dependency_type'] ) ) : array();
		$target_types   = isset( $_POST['qrhunt_dependency_target_type'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['qrhunt_dependency_target_type'] ) ) : array();
		$checkpoint_ids = isset( $_POST['qrhunt_dependency_checkpoint_id'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['qrhunt_dependency_checkpoint_id'] ) ) : array();
		$group_ids      = isset( $_POST['qrhunt_dependency_group_id'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['qrhunt_dependency_group_id'] ) ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$dependencies   = array();
		$row_indexes    = array_unique( array_merge( array_keys( $types ), array_keys( $target_types ), array_keys( $checkpoint_ids ), array_keys( $group_ids ) ) );

		foreach ( $row_indexes as $row_index ) {
			$type        = isset( $types[ $row_index ] ) ? $types[ $row_index ] : '';
			$target_type = isset( $target_types[ $row_index ] ) ? $target_types[ $row_index ] : '';
			$target_id   = 'group' === $target_type
				? ( isset( $group_ids[ $row_index ] ) ? $group_ids[ $row_index ] : 0 )
				: ( isset( $checkpoint_ids[ $row_index ] ) ? $checkpoint_ids[ $row_index ] : 0 );

			if ( ! in_array( $type, array( 'before', 'after' ), true ) || ! in_array( $target_type, array( 'checkpoint', 'group' ), true ) || 0 === $target_id ) {
				continue;
			}

			if ( 'checkpoint' === $target_type && $checkpoint_id === $target_id ) {
				continue;
			}

			$dependency = new Dependency();
			$dependency->set_checkpoint_id( $checkpoint_id );
			$dependency->set_type( $type );
			$dependency->set_target_type( $target_type );
			$dependency->set_target_id( $target_id );
			$dependencies[] = $dependency;
		}

		return $dependencies;
	}

	/**
	 * Builds Checkpoint titles indexed by post ID.
	 *
	 * @param array<int, \QRHunt\Model\Checkpoint> $checkpoints Checkpoints.
	 * @return array<int, string>
	 */
	private function get_checkpoint_titles( array $checkpoints ): array {
		$checkpoint_titles = array();

		foreach ( $checkpoints as $checkpoint ) {
			$checkpoint_titles[ $checkpoint->get_post_id() ] = get_the_title( $checkpoint->get_post_id() );
		}

		return $checkpoint_titles;
	}
}
