<?php
namespace QRHunt\Service;

use QRHunt\Model\Group;
use QRHunt\Repository\GroupRepository;

defined( 'ABSPATH' ) || exit;

final class GroupService {
	private $group_repository;
	public function __construct( GroupRepository $group_repository ) { $this->group_repository = $group_repository; }
	public function get_groups(): array { return $this->group_repository->find_all(); }
	public function save_group( Group $group ): void { $this->group_repository->save( $group ); }
	public function delete_group( int $id ): void { $this->group_repository->delete( $id ); }
}
