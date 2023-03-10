<?php

namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Prod_Type_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Prod_Type extends Woocrm_Controller {

	private Prod_Type_Service $prod_type_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'prod-type/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PRODUCT_TYPE', 'PRODUCT' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT_TYPE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/show/(?P<prod_type_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'PRODUCT_TYPE', 'PRODUCT' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT_TYPE' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'prod-type/delete/(?P<prod_type_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'PRODUCT_TYPE' ) );
				},
			)
		);

		$this->prod_type_service = new Prod_Type_Service();
	}
	/**
	 * Searching product type.
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params     = $request->get_params();
		$prod_types = $this->prod_type_service->select_list_prod_type( $params );
		return rest_ensure_response( $prod_types );
	}
	/**
	 * show detail a product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();

		return rest_ensure_response(
			$this->prod_type_service->select_one_prod_type( $params['prod_type_id'] )
		);
	}

	/**
	 * add new product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();

		$prod_type_id = $this->prod_type_service->insert_prod_type( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'prod_type_id' => $prod_type_id,
			)
		);
	}
	/**
	 * update product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->prod_type_service->update_prod_type( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'prod_type_id' => $params['prod_type_id'],
			)
		);
	}
	/**
	 * delete product type
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->prod_type_service->delete_prod_type( $params['prod_type_id'] );
		return rest_ensure_response(
			array(
				'prod_type_id' => $params['prod_type_id'],
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
		if ( 'prod_type_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'T??n lo???i s???n ph???m kh??ng h???p l???!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'T??n lo???i s???n ph???m kh??ng nh???p qu?? 255 k?? t???!' );
			}
			if ( $this->prod_type_service->count_exist_by_prod_type_nm( $value, $request['prod_type_id'] ) > 0 ) {
				return $this->form_valid( 'T??n lo???i s???n ph???m ???? t???n t???i!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
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
		if ( 'prod_type_id' == $param ) {
			if ( empty( $params['prod_type_id'] ) ) {
				return $this->form_valid( 'ID lo???i s???n ph???m l?? b???t bu???c!' );
			}
			if ( $this->prod_type_service->count_exist( $params['prod_type_id'] ) <= 0 ) {
				return $this->form_valid( 'ID lo???i s???n ph???m kh??ng t???n t???i!' );
			}
		}
	}

	/**
	 * We can use this function to contain our arguments for the customer endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args                 = $this->prefix_get_data_arguments_store();
		$args['prod_type_id'] = array(
			'description'       => esc_html__( 'ID lo???i s???n ph???m.', 'telsky' ),
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
			'prod_type_nm' => array(
				'description'       => esc_html__( 'T??n lo???i s???n ph???m.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'remark'       => array(
				'description'       => esc_html__( 'Ghi ch??.', 'telsky' ),
				'type'              => 'string',
				'required'          => false,
				'maxLength'         => 255,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'active'       => array(
				'description'       => esc_html__( 'T??nh tr???ng.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
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
			'title'      => 'prodtypes',
			'type'       => 'object',
			'properties' => array(
				'data'  => array(
					'description' => esc_html__( 'Danh s??ch lo???i s???n ph???m.', 'telsky' ),
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
			'title'      => 'prodtype',
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
			'prod_type_id' => array(
				'description' => esc_html__( 'ID lo???i s???n ph???m.', 'telsky' ),
				'type'        => 'integer',
			),
			'prod_type_nm' => array(
				'description' => esc_html__( 'T??n lo???i s???n ph???m.', 'telsky' ),
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
			'remark'       => array(
				'description' => esc_html__( 'Ghi ch??.', 'telsky' ),
				'type'        => 'string',
			),
		);
	}
}
