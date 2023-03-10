<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Partner_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Partner extends Woocrm_Controller {

	private Partner_Service $partner_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'partner/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PARTNER', 'PUR_ORDER', 'PUR_DEBT' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PARTNER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/show/(?P<partner_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PARTNER', 'PUR_ORDER', 'PUR_DEBT' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PARTNER' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'partner/delete/(?P<partner_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PARTNER' ) );
				},
			)
		);
		$this->partner_service = new Partner_Service();
	}
	/**
	 * Search Partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params   = $request->get_params();
		$partners = $this->partner_service->select_list_partner( $params );
		return rest_ensure_response( $partners );
	}
	/**
	 * Show detail partner.
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			$this->partner_service->select_one_partner( $params['partner_id'] )
		);
	}
	/**
	 * Add new partner
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params    = $request->get_params();
		$partner_id = $this->partner_service->insert_partner( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'partner_id' => $partner_id,
			)
		);
	}
	/**
	 * Update partner.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->partner_service->update_partner( $params, $request->user_login );
		return rest_ensure_response(
			array(
				'partner_id' => $params['partner_id'],
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
		if ( 'partner_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'M?? nh?? cung c???p kh??ng nh???p qu?? 50 k?? t???!' );
			}
			if ( $this->partner_service->count_exist_by_partner_cd( $value, $request['partner_id'] ) > 0 ) {
				return $this->form_valid( 'M?? nh?? cung c???p ???? t???n t???i!' );
			}
		}
		if ( 'partner_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'T??n nh?? cung c???p kh??ng h???p l???!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'T??n nh?? cung c???p kh??ng nh???p qu?? 255 k?? t???!' );
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
		if ( 'partner_id' == $param ) {
			if ( empty( $params['partner_id'] ) ) {
				return $this->form_valid( 'ID NCC l?? b???t bu???c!' );
			}
			if ( $this->partner_service->count_exist( $params['partner_id'] ) <= 0 ) {
				return $this->form_valid( 'Nh?? cung c???p kh??ng t???n t???i!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args               = $this->prefix_get_data_arguments_store();
		$args['partner_id'] = array(
			'description'       => esc_html__( 'Tham s??? ID NCC.', 'telsky' ),
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
			'partner_cd' => array(
				'description'       => esc_html__( 'Tham s??? m?? NCC.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'partner_nm' => array(
				'description'       => esc_html__( 'Tham s??? t??n NCC.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'phone'      => array(
				'description'       => esc_html__( 'Tham s??? s??? ??i???n tho???i.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'fax'        => array(
				'description'       => esc_html__( 'Tham s??? s??? Fax.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 20,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email'      => array(
				'description'       => esc_html__( 'Tham s??? email.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'address'    => array(
				'description'       => esc_html__( 'Tham s??? ?????a ch???.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'     => array(
				'description'       => esc_html__( 'Tham s??? ghi ch??.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'active'     => array(
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
	 * Delete partner.
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->partner_service->delete_partner( $params['partner_id'] );
		return rest_ensure_response(
			array(
				'partner_id' => $params['partner_id'],
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
					'description' => esc_html__( 'Danh s??ch NCC.', 'telsky' ),
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
			'partner_id'   => array(
				'description' => esc_html__( 'ID NCC.', 'telsky' ),
				'type'        => 'integer',
			),
			'partner_cd'   => array(
				'description' => esc_html__( 'M?? NCC.', 'telsky' ),
				'type'        => 'string',
			),
			'partner_nn'   => array(
				'description' => esc_html__( 'T??n NCC.', 'telsky' ),
				'type'        => 'string',
			),
			'address'      => array(
				'description' => esc_html__( '?????a ch??? NCC.', 'telsky' ),
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
