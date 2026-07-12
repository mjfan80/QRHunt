<?php
namespace QRHunt\Controller;

use QRHunt\Model\Group;
use QRHunt\Service\GroupService;
use QRHunt\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class GroupController {
	private $group_service;
	private $path_service;
	public function __construct( GroupService $group_service, PathService $path_service ) { $this->group_service = $group_service; $this->path_service = $path_service; }
	public function register_page(): void { add_submenu_page( 'qrhunt', __( 'Groups', 'qrhunt' ), __( 'Groups', 'qrhunt' ), 'edit_posts', 'qrhunt-groups', array( $this, 'render_page' ) ); }
	public function render_page(): void { ?>
		<div class="wrap"><h1><?php esc_html_e( 'Groups', 'qrhunt' ); ?></h1>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'qrhunt_save_group', 'qrhunt_group_nonce' ); ?><input type="hidden" name="action" value="qrhunt_save_group" /><p><select name="path_id" required><option value=""><?php esc_html_e( 'Select a Path', 'qrhunt' ); ?></option><?php foreach ( $this->path_service->get_paths() as $path ) : ?><option value="<?php echo esc_attr( (string) $path->get_id() ); ?>"><?php echo esc_html( $path->get_name() ); ?></option><?php endforeach; ?></select></p><p><input name="name" required /></p><p><textarea name="description"></textarea></p><?php submit_button( __( 'Add Group', 'qrhunt' ) ); ?></form>
		<table class="widefat"><tbody><?php foreach ( $this->group_service->get_groups() as $group ) : ?><tr><td><?php echo esc_html( $group->get_name() ); ?></td><td><?php echo esc_html( (string) $group->get_path_id() ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'qrhunt_delete_group_' . $group->get_id(), 'qrhunt_group_nonce' ); ?><input type="hidden" name="action" value="qrhunt_delete_group" /><input type="hidden" name="group_id" value="<?php echo esc_attr( (string) $group->get_id() ); ?>" /><button type="submit" class="button-link-delete"><?php esc_html_e( 'Delete', 'qrhunt' ); ?></button></form></td></tr><?php endforeach; ?></tbody></table></div>
		<?php }
	public function save(): void { if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_group_nonce'] ) ), 'qrhunt_save_group' ) ) { wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) ); } $group = new Group(); $group->set_path_id( absint( wp_unslash( $_POST['path_id'] ?? 0 ) ) ); $group->set_name( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ) ); $group->set_description( sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) ); $this->group_service->save_group( $group ); wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-groups' ) ); exit; }
	public function delete(): void { $id = absint( wp_unslash( $_POST['group_id'] ?? 0 ) ); if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['qrhunt_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qrhunt_group_nonce'] ) ), 'qrhunt_delete_group_' . $id ) ) { wp_die( esc_html__( 'Invalid request.', 'qrhunt' ) ); } $this->group_service->delete_group( $id ); wp_safe_redirect( admin_url( 'admin.php?page=qrhunt-groups' ) ); exit; }
}
