<?php
namespace Telsky\Woocrm\Controller;

use Telsky\Woocrm\Services\Quotation_Service;
use Utility;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 *
 * @version 1.0
 * @package WooCRM
 */

class Woocrm_Quotation extends Woocrm_Controller {

	private Quotation_Service $quotation_service;
	public function __construct() {
		register_rest_route(
			$this->get_namespace(),
			'quotation/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'QUOTATION', 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_items_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/store',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'store' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
				'args'                => $this->prefix_get_data_arguments_store(),
			),
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/show/(?P<quo_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => function ( WP_REST_Request $request ) {
						return $this->has_role( $request, array( 'QUOTATION', 'SALE_ORDER' ) );
					},
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
				'args'                => $this->prefix_get_data_arguments_for_update(),
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/delete/(?P<quo_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
			)
		);

		register_rest_route(
			$this->get_namespace(),
			'quotation/download/(?P<quo_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					return $this->has_role( $request, array( 'QUOTATION' ) );
				},
			)
		);

		$this->quotation_service = new Quotation_Service();
	}
	/**
	 * Search Quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function search( WP_REST_Request $request ) {
		$params = $request->get_params();
		$quos   = $this->quotation_service->select_list_quotation( $params );
		return rest_ensure_response( $quos );
	}
	/**
	 * View detail a quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function show( WP_REST_Request $request ) {
		$params = $request->get_params();
		$quo_id = $params['quo_id'];

		return rest_ensure_response(
			array(
				'quotation' => $this->quotation_service->select_one_quotation( $quo_id ),
				'products'  => $this->quotation_service->select_list_quo_item( $quo_id ),
			)
		);
	}
	/**
	 * Add new a quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function store( WP_REST_Request $request ) {
		 $params = $request->get_params();
		$quo_id  = $this->quotation_service->insert_quotation( $params, $request->user_login );

		return rest_ensure_response(
			array(
				'quo_id' => $quo_id,
			)
		);
	}
	/**
	 * update quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function update( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->quotation_service->update_quotation( $params, $request->user_login );
		return rest_ensure_response(
			array(
				'quo_id' => $params['quo_id'],
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
		 $params = $request->get_params();
		if ( 'quo_cd' === $param ) {
			if ( strlen( $value ) > 50 ) {
				return $this->form_valid( 'M?? b??o gi?? kh??ng nh???p qu?? 50 k?? t???!' );
			}
			if ( $this->quotation_service->count_exist_by_quo_cd( $value, $request['quo_id'] ) > 0 ) {
				return $this->form_valid( 'M?? b??o gi?? ???? t???n t???i!' );
			}
		}
		if ( 'quo_nm' === $param ) {
			if ( ! is_string( $value ) ) {
				return $this->form_valid( 'T??n b??o gi?? kh??ng h???p l???!' );
			}
			if ( strlen( $value ) > 255 ) {
				return $this->form_valid( 'T??n b??o gi?? kh??ng nh???p qu?? 255 k?? t???!' );
			}
		}
		if ( 'remark' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
		}
		if ( 'quo_date' === $param && strlen( $value ) > 255 ) {
			return $this->form_valid( 'Ghi ch?? kh??ng nh???p qu?? 255 k?? t???!' );
		}
		if ( 'product_id' === $param ) {
			if ( empty( $value ) ) {
				return $this->form_valid( 'Danh s??ch s???n ph???m trong ????n h??ng kh??ng h???p l???!' );
			}
			if (
				empty( $params['quantity'] )
				|| empty( $params['unit_price'] )
				|| count( $value ) !== count( $params['quantity'] )
				|| count( $value ) !== count( $params['unit_price'] ) ) {
					return $this->form_valid( 'Danh s??ch s???n ph???m trong ????n h??ng kh??ng h???p l???!' );
			}
			foreach ( $params['product_id'] as $index => $prod_id ) {
				if ( empty( $params['quantity'][ $index ] ) ) {
					return $this->form_valid( 'Ch??a nh???p s??? l?????ng s???n ph???m!' );
				}
				if ( empty( $params['unit_price'][ $index ] ) ) {
					return $this->form_valid( 'Ch??a nh???p ????n gi?? s???n ph???m!' );
				}
			}
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
		if ( 'quo_id' == $param ) {
			if ( empty( $params['quo_id'] ) ) {
				return $this->form_valid( 'ID b??o gi?? l?? b???t bu???c!' );
			}
			if ( $this->quotation_service->count_exists( $params['quo_id'] ) <= 0 ) {
				return $this->form_valid( 'B??o gi?? kh??ng t???n t???i!' );
			}
		}
	}
	/**
	 * We can use this function to contain our arguments for the quatation endpoint.
	 */
	public function prefix_get_data_arguments_for_update() {
		$args           = $this->prefix_get_data_arguments_store();
		$args['quo_id'] = array(
			'description'       => esc_html__( 'Tham s??? ID b??o gi??.', 'telsky' ),
			'type'              => 'integer',
			'required'          => true,
			'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
		);
		return $args;
	}
	/**
	 * We can use this function to contain our arguments for the quatation endpoint.
	 */
	public function prefix_get_data_arguments_store() {
		return array(

			'quo_cd'     => array(
				'description'       => esc_html__( 'Tham s??? m?? b??o gi??.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 50,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_nm'     => array(
				'description'       => esc_html__( 'Tham s??? t??n b??o gi??.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
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
			'quo_date'   => array(
				'description'       => esc_html__( 'Tham s??? ng??y b??o gi??.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_type'   => array(
				'description'       => esc_html__( 'Lo???i b??o gi??.', 'telsky' ),
				'type'              => 'string',
				'required'          => true,
				'maxLength'         => 1,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quo_total'  => array(
				'description'       => esc_html__( 'Tham s??? t???ng gi?? tr???.', 'telsky' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'quantity'   => array(
				'description'       => esc_html__( 'S??? l?????ng s???n ph???m.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'unit_price' => array(
				'description'       => esc_html__( '????n gi?? gi???n ph???m.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
			'product_id' => array(
				'description'       => esc_html__( 'Id s???n ph???m.', 'telsky' ),
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => array( $this, 'prefix_data_arg_validate_callback' ),
			),
		);
	}
	/**
	 * delete quotation
	 *
	 * @param WP_REST_Request $request
	 */
	public function delete( WP_REST_Request $request ) {
		$params = $request->get_params();
		$this->quotation_service->delete_quotation( $params['quo_id'] );
		return rest_ensure_response(
			array(
				'quo_id' => $params['quo_id'],
			)
		);
	}

	/**
	 * Get item object schema.
	 *
	 * @return array The quotation schema for response.
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
					'description' => esc_html__( 'Danh s??ch b??o gi??.', 'telsky' ),
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
			'title'      => 'quotation',
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
			'quo_id'       => array(
				'description' => esc_html__( 'ID b??o gi??.', 'telsky' ),
				'type'        => 'integer',
			),
			'quo_cd'       => array(
				'description' => esc_html__( 'M?? b??o gi??.', 'telsky' ),
				'type'        => 'string',
			),
			'quo_nm'       => array(
				'description' => esc_html__( 'T??n b??o gi??.', 'telsky' ),
				'type'        => 'string',
			),
			'quo_date'     => array(
				'description' => esc_html__( 'Ng??y b??o gi??.', 'telsky' ),
				'type'        => 'string',
			),
			'quo_type'     => array(
				'description' => esc_html__( 'Lo???i b??o gi??.', 'telsky' ),
				'type'        => 'string',
			),
			'total_value'  => array(
				'description' => esc_html__( 'T???ng gi?? tr???.', 'telsky' ),
				'type'        => 'string',
			),
			'quantity'     => array(
				'description' => esc_html__( 'S??? l?????ng.', 'telsky' ),
				'type'        => 'string',
			),
			'remark'       => array(
				'description' => esc_html__( 'Ghi ch??.', 'telsky' ),
				'type'        => 'string',
			),
			'unit_price'   => array(
				'description' => esc_html__( '????n gi??.', 'telsky' ),
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
