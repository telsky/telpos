<?php
namespace Telsky\Woocrm\Services;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Customer_Service extends Base_Service {

	public function __construct() {
		 // TODO:
	}

	/**
	 * check exists customer code
	 */
	public function count_exist_by_customer_cd( string $customer_cd, $customer_id = null ): int {
		global $wpdb;
		$sql      = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_customer WHERE customer_cd = %s';
		$wheres[] = $customer_cd;
		if ( ! empty( $customer_id ) ) {
			$sql     .= ' AND customer_id != %s ';
			$wheres[] = $customer_id;
		}
		return $wpdb->get_var( $wpdb->prepare( $sql, $wheres ) ) > 0;
	}

	/**
	 * check exists customer id
	 *
	 * @param int $customer_id
	 * @return int
	 */
	public function count_exist( $customer_id ): int {
		global $wpdb;
		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocrm_customer WHERE customer_id = %d',
				$customer_id
			)
		);
	}

	/**
	 * Delete customer.
	 *
	 * @param int $customer_id
	 *
	 * @return void
	 */
	public function delete_customer( int $customer_id ): void {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'woocrm_customer', array( 'customer_id' => $customer_id ) );
	}

	/**
	 * Update customer.
	 *
	 * @param array  $params
	 * @param string $user_login
	 *
	 * @return void
	 */
	public function update_customer( $params, string $user_login ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocrm_customer',
			array(
				'customer_cd'  => $params['customer_cd'],
				'customer_nm'  => $params['customer_nm'],
				'address'      => $params['address'],
				'phone'        => $params['phone'],
				'fax'          => $params['fax'],
				'email'        => $params['email'],
				'active'       => $params['active'],
				'remark'       => $params['remark'],
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'updated_user' => $user_login,
			),
			array(
				'customer_id' => $params['customer_id'],
			),
			array( '%s' )
		);
	}

	/**
	 * Insert customer.
	 *
	 * @param array $params
	 *
	 * @return int customer id.
	 */
	public function insert_customer( $params, string $user_login ): int {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocrm_customer',
			array(
				'customer_cd'  => $params['customer_cd'],
				'customer_nm'  => $params['customer_nm'],
				'address'      => $params['address'],
				'phone'        => $params['phone'],
				'fax'          => $params['fax'] ?? null,
				'email'        => $params['email'],
				'active'       => $params['active'] ?? 'Y',
				'remark'       => $params['remark'] ?? null,
				'created_at'   => date( 'Y-m-d H:i:s' ),
				'updated_at'   => date( 'Y-m-d H:i:s' ),
				'created_user' => $user_login,
				'updated_user' => $user_login,
			),
			array( '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get one customer.
	 *
	 * @param int $customer_id
	 * @return object
	 */
	public function select_one_customer( int $customer_id ): object {
		global $wpdb;

		$sql  = 'SELECT a.* FROM ' . $wpdb->prefix . 'woocrm_customer as a';
		$sql .= ' WHERE a.customer_id = %d ';
		return $wpdb->get_row( $wpdb->prepare( $sql, $customer_id ) );
	}

	/**
	 * Get list customer.
	 *
	 * @param array $param
	 *
	 * @return array
	 */
	public function select_list_customer( $params ): array {
		global $wpdb;

		$sql_select = '  SELECT a.*';
		$sql_from   = ' FROM ' . $wpdb->prefix . 'woocrm_customer as a ';
		$sql_where  = ' WHERE 1 = 1 ';
		$wheres     = array();
		if ( ! empty( $params['active'] ) ) {
			$sql_where .= ' AND a.active = %s ';
			$wheres[]   = $params['active'];
		}
		if ( ! empty( $params['customer_cd'] ) ) {
			$sql_where .= ' AND a.customer_cd = %s ';
			$wheres[]   = $params['customer_cd'];
		}
		if ( ! empty( $params['customer_nm'] ) ) {
			$sql_where .= ' AND a.customer_nm LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['customer_nm'] ) ) ) . '%';
		}
		if ( ! empty( $params['phone'] ) ) {
			$sql_where .= ' AND a.phone = %s ';
			$wheres[]   = $params['phone'];
		}
		if ( ! empty( $params['email'] ) ) {
			$sql_where .= ' AND a.email LIKE %s ';
			$wheres[]   = '%' . esc_sql( $wpdb->esc_like( trim( $params['email'] ) ) ) . '%';
		}
        $sql_where .= ' ORDER BY a.customer_id DESC';

		return $this->query_paging(
			$sql_select,
			$sql_from,
			$sql_where,
			$wheres,
			WOOCRM_PAGE_SIZE,
			$params['current']
		);
	}

}
