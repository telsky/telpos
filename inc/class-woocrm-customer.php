<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Customer_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Customer extends Woocrm_Controller {

	private Customer_Service $customer_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'customer/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'CUSTOMER', 'SALE_ORDER', 'SALE_DEBT', 'QUOTATION' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'CUSTOMER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			),
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/show/(?P<customer_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'CUSTOMER', 'SALE_ORDER', 'SALE_DEBT', 'QUOTATION' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'CUSTOMER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'customer/delete/(?P<customer_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'CUSTOMER' ) );
				},
			)
		);

		$this->customer_service = new Customer_Service();
	}
	/**
	 * Search partners.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			$this->customer_service->select_list_customer( $params )
		);
	}
	/**
	 * view detail customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		return rest_ensure_response(
			$this->customer_service->select_one_customer( $params['customer_id'] )
		);
	}
	/**
	 * add new customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params     = $request->get_params();
		$customer_id = $this->customer_service->insert_customer( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'customer_id' => $customer_id,
			)
		);
	}
	/**
	 * update customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->customer_service->update_customer( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'customer_id' => $params['customer_id'],
			)
		);
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_data_arg_validate_callback( $value, $request, $param ) {
		if ( 'customer_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'M?? kh??ch h??ng kh??ng nh???p qu?? 50 k?? t???!' );
			}
			if ( $this->customer_service->count_exist_by_customer_cd( $value, $request['customer_id'] ) > 0 ) {
				return $this->form_valid( 'M?? kh??ch h??ng ???? t???n t???i!' );
			}
		}
		if ( 'customer_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'T??n kh??ch h??ng kh??ng h???p l???!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'T??n kh??ch h??ng kh??ng nh???p qu?? 255 k?? t???!' );
			}
		}
		if ( 'address' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( '?????a ch??? kh??ng h???p l???!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( '?????a ch??? kh??ng nh???p qu?? 255 k?? t???!' );
			}
		}
		if ( 'phone' === $param && strlen( $value ) > 20 ) {
			return $this->form_valid( 'S??? ??i???n tho???i kh??ng nh???p qu?? 20 k?? t???!' );
		}
		if ( 'fax' === $param && strlen( $value ) > 20 ) {
			return $this->form_valid( 'S??? Fax kh??ng nh???p qu?? 20 k?? t???!' );
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
		}
		if ( 'email' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'Email kh??ng h???p l???!' );
			}
			if ( strlen( $value ) === 0 ) {
				return true;
			}
			if ( ! is_email( $value ) ) {
				return $this->form_valid( 'Kh??ng ????ng ?????nh d???ng email!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'Email kh??ng nh???p qu?? 255 k?? t???!' );
			}
		}
		if ( 'active' === $param && 'N' !== $value && 'Y' !== $value ) {
			return $this->form_valid( 'Tr???ng th??i kh??ng h???p l??? Y|N' );
		}
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value of the argument.
	 * @param  WP_REST_Request $request The current request object.
	 * @param  string          $param   Key of the parameter.
	 * @return WP_Error|boolean
	 */
	public function prefix_update_data_arg_validate_callback( $value, $request, $param ) {
		$this->prefix_data_arg_validate_callback( $value, $request, $param );
		if ( 'customer_id' == $param ) {
			if ( empty( $params['customer_id'] ) ) {
				return $this->form_valid( 'ID kh??ch h??ng l?? b???t bu???c!' );
			}
			if ( $this->customer_service->count_exist( $params['customer_id'] ) <= 0 ) {
				return $this->form_valid( 'Kh??ch h??ng kh??ng t???n t???i!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args                = $this->prefix_get_data_arguments_store();
		$args['customer_id'] = array(
			'description'       => esc_html__( 'Tham s??? ID kh??ch h??ng.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		return array(
			'customer_cd' => array(
				'description'       => esc_html__( 'Tham s??? m?? kh??ch h??ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'customer_nm' => array(
				'description'       => esc_html__( 'Tham s??? t??n kh??ch h??ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone'       => array(
				'description'       => esc_html__( 'Tham s??? s??? ??i???n tho???i.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'fax'         => array(
				'description'       => esc_html__( 'Tham s??? s??? Fax.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email'       => array(
				'description'       => esc_html__( 'Tham s??? email.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address'     => array(
				'description'       => esc_html__( 'Tham s??? ?????a ch???.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'      => array(
				'description'       => esc_html__( 'Tham s??? ghi ch??.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'active'      => array(
				'description'       => esc_html__( 'Tham s??? t??nh tr???ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
	/**
	 * delete customer
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();

		$this->customer_service->delete_customer( $params['customer_id'] );
		return rest_ensure_response(
			array(
				'customer_id' => $params['customer_id'],
			)
		);
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The customers schema for response.
	 */
	public function get_items_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'item',
			'type'       => 'object',
			'properties' => array(
				'data'  => array(
					'description' => esc_html__( 'Danh s??ch kh??ch h??ng.', 'telsky' ),
					'type'        => 'array',
					'items'       => $this->item_schema(),
				),
				'total' => array(
					'description' => esc_html__( 'T???ng s??? b???n ghi t??m th???y.', 'telsky' ),
					'type'        => 'integer',
				),
			),
		);

		return $this->schema;
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The customer schema for response.
	 */
	public function get_item_schema() {
		// Get schema from cache.
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'customer',
			'type'       => 'object',
			'properties' => $this->item_schema(),
		);

		return $this->schema;
	}

	/**
	 * Base item for schema object.
	 */
	private function item_schema() {
		return array(
			'customer_id'  => array(
				'description' => esc_html__( 'ID kh??ch h??ng.', 'telsky' ),
				'type'        => 'integer',
			),
			'customer_cd'  => array(
				'description' => esc_html__( 'M?? kh??ch h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'customer_nm'  => array(
				'description' => esc_html__( 'T??n kh??ch h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'address'      => array(
				'description' => esc_html__( '?????a ch??? kh??ch h??ng.', 'telsky' ),
				'type'        => 'string',
			),
			'phone'        => array(
				'description' => esc_html__( 'S??? ??i???n tho???i.', 'telsky' ),
				'type'        => 'string',
			),
			'fax'          => array(
				'description' => esc_html__( 'Fax.', 'telsky' ),
				'type'        => 'string',
			),
			'email'        => array(
				'description' => esc_html__( 'Email.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi ch??.', 'telsky' ),
				'type'        => 'string',
			),
			'active'       => array(
				'description' => esc_html__( 'T??nh tr???ng.', 'telsky' ),
				'type'        => 'string',
			),
			'updated_at'   => array(
				'description' => esc_html__( 'Ng??y c???p nh???t.', 'telsky' ),
				'type'        => 'date-time',
			),
			'updated_user' => array(
				'description' => esc_html__( 'Ng?????i c???p nh???t.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
