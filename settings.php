<?php 
require_once GMS_PLUGIN_DIR. '/gmgt_function.php';
require_once GMS_PLUGIN_DIR. '/class/membership.php';
require_once GMS_PLUGIN_DIR. '/class/group.php';
require_once GMS_PLUGIN_DIR. '/class/member.php';
require_once GMS_PLUGIN_DIR. '/class/class_schedule.php';
require_once GMS_PLUGIN_DIR. '/class/product.php';
require_once GMS_PLUGIN_DIR. '/class/store.php';
require_once GMS_PLUGIN_DIR. '/class/reservation.php';
require_once GMS_PLUGIN_DIR. '/class/attendence.php';
require_once GMS_PLUGIN_DIR. '/class/membership_payment.php';
require_once GMS_PLUGIN_DIR. '/class/payment.php';
require_once GMS_PLUGIN_DIR. '/class/activity.php';
require_once GMS_PLUGIN_DIR. '/class/workout_type.php';
require_once GMS_PLUGIN_DIR. '/class/workout.php';
require_once GMS_PLUGIN_DIR. '/class/notice.php';
require_once GMS_PLUGIN_DIR. '/class/nutrition.php';
require_once GMS_PLUGIN_DIR. '/class/MailChimp.php';
require_once GMS_PLUGIN_DIR. '/class/MCAPI.class.php';
require_once GMS_PLUGIN_DIR. '/class/gym-management.php';
require_once GMS_PLUGIN_DIR. '/class/dashboard.php';
require_once GMS_PLUGIN_DIR. '/class/message.php';
require_once GMS_PLUGIN_DIR. '/class/tax.php';
require_once GMS_PLUGIN_DIR. '/lib/paypal/paypal_class.php';

add_action( 'admin_head', 'MJ_gmgt_admin_css' );
//ADMIN SIDE CSS FUNCTION
function MJ_gmgt_admin_css()
{
	?>
    <style>
    a.toplevel_page_gmgt_system:hover,  a.toplevel_page_gmgt_system:focus,.toplevel_page_gmgt_system.opensub a.wp-has-submenu{
	 background: url("<?php echo GMS_PLUGIN_URL;?>/assets/images/gym-2.png") no-repeat scroll 8px 9px rgba(0, 0, 0, 0) !important;
	  
	}
	.toplevel_page_gmgt_system:hover .wp-menu-image.dashicons-before img {
	  display: none;
	}

	.toplevel_page_gmgt_system:hover .wp-menu-image.dashicons-before {
	  min-width: 23px !important;
	}  
	</style>
	<?php
}
add_action('init', 'MJ_gmgt_session_manager'); 
//SESSION MANAGER FUNCTION
function MJ_gmgt_session_manager() 
{	
	if (!session_id())
	{
		session_start();		
		if(!isset($_SESSION['gmgt_verify']))
		{			
			$_SESSION['gmgt_verify'] = '';
		}		
	}	
}
//LOGOUT FUNCTION 
function MJ_gmgt_logout()
{
	if(isset($_SESSION['gmgt_verify']))
	{ 
		unset($_SESSION['gmgt_verify']);
	}   
}
add_action('wp_logout','MJ_gmgt_logout');
 
add_action('init','MJ_gmgt_setup');
function MJ_gmgt_setup()
{
	$_SESSION['gmgt_verify'] = '0';
	$is_cmgt_pluginpage = MJ_gmgt_is_gmgtpage();
	$is_verify = false;
	if(!isset($_SESSION['gmgt_verify']))
		$_SESSION['gmgt_verify'] = '';
	$server_name = $_SERVER['SERVER_NAME'];
	$is_localserver = MJ_gmgt_chekserver($server_name);
	if($is_localserver)
	{		
		return true;
	}
	
	if($is_cmgt_pluginpage)
	{	
		if($_SESSION['gmgt_verify'] == '')
		{		
			if( get_option('licence_key') && get_option('gmgt_setup_email'))
			{			
				$domain_name = $_SERVER['SERVER_NAME'];
				$licence_key = get_option('licence_key');
				$email = get_option('gmgt_setup_email');
				$result = MJ_gmgt_check_productkey($domain_name,$licence_key,$email);
				$is_server_running = MJ_gmgt_check_ourserver();
				if($is_server_running)
					$_SESSION['gmgt_verify'] =$result;
				else
					$_SESSION['gmgt_verify'] = '0';
				$is_verify = MJ_gmgt_check_verify_or_not($result);
			
			}
		}
	}
	$is_verify = MJ_gmgt_check_verify_or_not($_SESSION['gmgt_verify']);
	if($is_cmgt_pluginpage)
		if(!$is_verify)
		{
			if($_REQUEST['page'] != 'gmgt_setup')
			wp_redirect(admin_url().'admin.php?page=gmgt_setup');
		}
}

if ( is_admin() )
{
	require_once GMS_PLUGIN_DIR. '/admin/admin.php';
	//INSTALL ROLE AND TABLE FUNCTION
	function MJ_gmgt_install()
	{
			add_role('staff_member', __( 'Instructor' ,'gym_mgt'),array( 'read' => true, 'level_1' => true ));
			add_role('accountant', __( 'Accountant' ,'gym_mgt'),array( 'read' => true, 'level_1' => true ));
			add_role('member', __( 'Member' ,'gym_mgt'),array( 'read' => true, 'level_0' => true ));
			
			MJ_gmgt_install_tables();			
	}
	register_activation_hook(GMS_PLUGIN_BASENAME, 'MJ_gmgt_install' );
	//ADD OPTION FUNCTION
	function MJ_gmgt_option()
	{		
		$access_right_member = array();
		$access_right_member['member'] = [
							"staff_member"=>["menu_icone"=>plugins_url('gym-management/assets/images/icon/staff-member.png'),
							           "menu_title"=>'Staff Members',
							           "page_link"=>'staff_member',
									   "own_data" =>isset($_REQUEST['staff_member_own_data'])?$_REQUEST['staff_member_own_data']:0,
									   "add" =>isset($_REQUEST['staff_member_add'])?$_REQUEST['staff_member_add']:0,
										"edit"=>isset($_REQUEST['staff_member_edit'])?$_REQUEST['staff_member_edit']:0,
										"view"=>isset($_REQUEST['staff_member_view'])?$_REQUEST['staff_member_view']:1,
										"delete"=>isset($_REQUEST['staff_member_delete'])?$_REQUEST['staff_member_delete']:0
										],
												
						   "membership"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/membership-type.png'),
						              "menu_title"=>'Membership Type',
						              "page_link"=>'membership',
									 "own_data" => isset($_REQUEST['membership_own_data'])?$_REQUEST['membership_own_data']:0,
									 "add" => isset($_REQUEST['membership_add'])?$_REQUEST['membership_add']:0,
									 "edit"=>isset($_REQUEST['membership_edit'])?$_REQUEST['membership_edit']:0,
									 "view"=>isset($_REQUEST['membership_view'])?$_REQUEST['membership_view']:1,
									 "delete"=>isset($_REQUEST['membership_delete'])?$_REQUEST['membership_delete']:0
						  ],
									  
							"group"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/group.png'),
							        "menu_title"=>'Group',
									"page_link"=>'group',
									 "own_data" => isset($_REQUEST['group_own_data'])?$_REQUEST['group_own_data']:0,
									 "add" => isset($_REQUEST['group_add'])?$_REQUEST['group_add']:0,
									"edit"=>isset($_REQUEST['group_edit'])?$_REQUEST['group_edit']:0,
									"view"=>isset($_REQUEST['group_view'])?$_REQUEST['group_view']:1,
									"delete"=>isset($_REQUEST['group_delete'])?$_REQUEST['group_delete']:0
						  ],
									  
							  "member"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/member.png'),
							            "menu_title"=>'Member',
										"page_link"=>'member',
										"own_data" => isset($_REQUEST['member_own_data'])?$_REQUEST['member_own_data']:1,
										 "add" => isset($_REQUEST['member_add'])?$_REQUEST['member_add']:0,
										 "edit"=>isset($_REQUEST['member_edit'])?$_REQUEST['member_edit']:0,
										"view"=>isset($_REQUEST['member_view'])?$_REQUEST['member_view']:1,
										"delete"=>isset($_REQUEST['member_delete'])?$_REQUEST['member_delete']:0
							  ],
							  
							  "activity"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/activity.png'),
							             "menu_title"=>'Activity',
										 "page_link"=>'activity',
										 "own_data" => isset($_REQUEST['activity_own_data'])?$_REQUEST['activity_own_data']:0,
										 "add" => isset($_REQUEST['activity_add'])?$_REQUEST['activity_add']:0,
										"edit"=>isset($_REQUEST['activity_edit'])?$_REQUEST['activity_edit']:0,
										"view"=>isset($_REQUEST['activity_view'])?$_REQUEST['activity_view']:1,
										"delete"=>isset($_REQUEST['activity_delete'])?$_REQUEST['activity_delete']:0
							  ],
							  "class-schedule"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/class-schedule.png'),
							               "menu_title"=>'Class schedule',
										   "page_link"=>'class-schedule',
										 "own_data" => isset($_REQUEST['class_schedule_own_data'])?$_REQUEST['class_schedule_own_data']:0,
										 "add" => isset($_REQUEST['class_schedule_add'])?$_REQUEST['class_schedule_add']:0,
										"edit"=>isset($_REQUEST['class_schedule_edit'])?$_REQUEST['class_schedule_edit']:0,
										"view"=>isset($_REQUEST['class_schedule_view'])?$_REQUEST['class_schedule_view']:1,
										"delete"=>isset($_REQUEST['class_schedule_delete'])?$_REQUEST['class_schedule_delete']:0
							  ],
							  
							    "attendence"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/attandance.png'),
								         "menu_title"=>'Attendence',
										 "page_link"=>'attendence',
										 "own_data" => isset($_REQUEST['attendence_own_data'])?$_REQUEST['attendence_own_data']:0,
										 "add" => isset($_REQUEST['attendence_add'])?$_REQUEST['attendence_add']:0,
										"edit"=>isset($_REQUEST['attendence_edit'])?$_REQUEST['attendence_edit']:0,
										"view"=>isset($_REQUEST['attendence_view'])?$_REQUEST['attendence_view']:0,
										"delete"=>isset($_REQUEST['attendence_delete'])?$_REQUEST['attendence_delete']:0
							  ],						  
							  
							    "assign-workout"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/assigne-workout.png'),
								         "menu_title"=>'Assigned Workouts',
										 "page_link"=>'assign-workout',
										 "own_data" => isset($_REQUEST['assign_workout_own_data'])?$_REQUEST['assign_workout_own_data']:1,
										 "add" => isset($_REQUEST['assign_workout_add'])?$_REQUEST['assign_workout_add']:0,
										"edit"=>isset($_REQUEST['assign_workout_edit'])?$_REQUEST['assign_workout_edit']:0,
										"view"=>isset($_REQUEST['assign_workout_view'])?$_REQUEST['assign_workout_view']:1,
										"delete"=>isset($_REQUEST['assign_workout_delete'])?$_REQUEST['assign_workout_delete']:0
							  ],
							  "nutrition"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/nutrition-schedule.png'),
							            "menu_title"=>'Nutrition Schedule',
										"page_link"=>'nutrition',
										 "own_data" => isset($_REQUEST['nutrition_own_data'])?$_REQUEST['nutrition_own_data']:1,
										 "add" => isset($_REQUEST['nutrition_add'])?$_REQUEST['nutrition_add']:0,
										"edit"=>isset($_REQUEST['nutrition_edit'])?$_REQUEST['nutrition_edit']:0,
										"view"=>isset($_REQUEST['nutrition_view'])?$_REQUEST['nutrition_view']:1,
										"delete"=>isset($_REQUEST['nutrition_delete'])?$_REQUEST['nutrition_delete']:0
							  ],
							    "workouts"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/workout.png'),
								         "menu_title"=>'Workouts',
										 "page_link"=>'workouts',
										 "own_data" => isset($_REQUEST['workouts_own_data'])?$_REQUEST['workouts_own_data']:1,
										 "add" => isset($_REQUEST['workouts_add'])?$_REQUEST['workouts_add']:1,
										"edit"=>isset($_REQUEST['workouts_edit'])?$_REQUEST['workouts_edit']:0,
										"view"=>isset($_REQUEST['workouts_view'])?$_REQUEST['workouts_view']:1,
										"delete"=>isset($_REQUEST['workouts_delete'])?$_REQUEST['workouts_delete']:0
							  ],
							    "accountant"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/accountant.png'),
								          "menu_title"=>'Accountant',
										  "page_link"=>'accountant',
										 "own_data" => isset($_REQUEST['accountant_own_data'])?$_REQUEST['accountant_own_data']:0,
										 "add" => isset($_REQUEST['accountant_add'])?$_REQUEST['accountant_add']:0,
										"edit"=>isset($_REQUEST['accountant_edit'])?$_REQUEST['accountant_edit']:0,
										"view"=>isset($_REQUEST['accountant_view'])?$_REQUEST['accountant_view']:1,
										"delete"=>isset($_REQUEST['accountant_delete'])?$_REQUEST['accountant_delete']:0
							  ],
							  
							  "membership_payment"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/fee.png'),
							             "menu_title"=>'Fee Payment',
										 "page_link"=>'membership_payment',
										 "own_data" => isset($_REQUEST['membership_payment_own_data'])?$_REQUEST['membership_payment_own_data']:1,
										 "add" => isset($_REQUEST['membership_payment_add'])?$_REQUEST['membership_payment_add']:0,
										"edit"=>isset($_REQUEST['membership_payment_edit'])?$_REQUEST['membership_payment_edit']:0,
										"view"=>isset($_REQUEST['membership_payment_view'])?$_REQUEST['membership_payment_view']:1,
										"delete"=>isset($_REQUEST['membership_payment_delete'])?$_REQUEST['membership_payment_delete']:0
							  ],
							  
							  "payment"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/payment.png'),
							             "menu_title"=>'Payment',
										 "page_link"=>'payment',
										 "own_data" => isset($_REQUEST['payment_own_data'])?$_REQUEST['payment_own_data']:1,
										 "add" => isset($_REQUEST['payment_add'])?$_REQUEST['payment_add']:0,
										"edit"=>isset($_REQUEST['payment_edit'])?$_REQUEST['payment_edit']:0,
										"view"=>isset($_REQUEST['payment_view'])?$_REQUEST['payment_view']:1,
										"delete"=>isset($_REQUEST['payment_delete'])?$_REQUEST['payment_delete']:0
							  ],
							  "product"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/products.png'),
							           "menu_title"=>'Product',
									   "page_link"=>'product',
										 "own_data" => isset($_REQUEST['product_own_data'])?$_REQUEST['product_own_data']:0,
										 "add" => isset($_REQUEST['product_add'])?$_REQUEST['product_add']:0,
										"edit"=>isset($_REQUEST['product_edit'])?$_REQUEST['product_edit']:0,
										"view"=>isset($_REQUEST['product_view'])?$_REQUEST['product_view']:1,
										"delete"=>isset($_REQUEST['product_delete'])?$_REQUEST['product_delete']:0
							  ],
							  "store"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/store.png'),
							              "menu_title"=>'Store',
										  "page_link"=>'store',
										 "own_data" => isset($_REQUEST['store_own_data'])?$_REQUEST['store_own_data']:1,
										 "add" => isset($_REQUEST['store_add'])?$_REQUEST['store_add']:0,
										"edit"=>isset($_REQUEST['store_edit'])?$_REQUEST['store_edit']:0,
										"view"=>isset($_REQUEST['store_view'])?$_REQUEST['store_view']:1,
										"delete"=>isset($_REQUEST['store_delete'])?$_REQUEST['store_delete']:0
							  ],
							  "news_letter"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/newsletter.png'),
							            "menu_title"=>'Newsletter',
										"page_link"=>'news_letter',
										 "own_data" => isset($_REQUEST['news_letter_own_data'])?$_REQUEST['news_letter_own_data']:0,
										 "add" => isset($_REQUEST['news_letter_add'])?$_REQUEST['news_letter_add']:0,
										"edit"=>isset($_REQUEST['news_letter_edit'])?$_REQUEST['news_letter_edit']:0,
										"view"=>isset($_REQUEST['news_letter_view'])?$_REQUEST['news_letter_view']:0,
										"delete"=>isset($_REQUEST['news_letter_delete'])?$_REQUEST['news_letter_delete']:0
							  ],
							  "message"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/message.png'),
							             "menu_title"=>'Message',
										 "page_link"=>'message',
										 "own_data" => isset($_REQUEST['message_own_data'])?$_REQUEST['message_own_data']:1,
										 "add" => isset($_REQUEST['message_add'])?$_REQUEST['message_add']:1,
										"edit"=>isset($_REQUEST['message_edit'])?$_REQUEST['message_edit']:0,
										"view"=>isset($_REQUEST['message_view'])?$_REQUEST['message_view']:1,
										"delete"=>isset($_REQUEST['message_delete'])?$_REQUEST['message_delete']:1
							  ],
							  
							   "notice"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/notice.png'),
							           "menu_title"=>'Notice',
									   "page_link"=>'notice',
										 "own_data" => isset($_REQUEST['notice_own_data'])?$_REQUEST['notice_own_data']:1,
										 "add" => isset($_REQUEST['notice_add'])?$_REQUEST['notice_add']:0,
										"edit"=>isset($_REQUEST['notice_edit'])?$_REQUEST['notice_edit']:0,
										"view"=>isset($_REQUEST['notice_view'])?$_REQUEST['notice_view']:1,
										"delete"=>isset($_REQUEST['notice_delete'])?$_REQUEST['notice_delete']:0
							  ],
							  
							   							  
							   "reservation"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/reservation.png'),							       
								         "menu_title"=>'Reservation',
										 "page_link"=>'reservation',
										 "own_data" => isset($_REQUEST['reservation_own_data'])?$_REQUEST['reservation_own_data']:0,
										 "add" => isset($_REQUEST['reservation_add'])?$_REQUEST['reservation_add']:0,
										"edit"=>isset($_REQUEST['reservation_edit'])?$_REQUEST['reservation_edit']:0,
										"view"=>isset($_REQUEST['reservation_view'])?$_REQUEST['reservation_view']:1,
										"delete"=>isset($_REQUEST['reservation_delete'])?$_REQUEST['reservation_delete']:0
							  ],
							  
							   "account"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/account.png'),
							              "menu_title"=>'Account',
										  "page_link"=>'account',
										 "own_data" => isset($_REQUEST['account_own_data'])?$_REQUEST['account_own_data']:0,
										 "add" => isset($_REQUEST['account_add'])?$_REQUEST['account_add']:0,
										"edit"=>isset($_REQUEST['account_edit'])?$_REQUEST['account_edit']:0,
										"view"=>isset($_REQUEST['account_view'])?$_REQUEST['account_view']:1,
										"delete"=>isset($_REQUEST['account_delete'])?$_REQUEST['account_delete']:0
							  ],
							   "subscription_history"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/subscription_history.png'),
							             "menu_title"=>'Subscription History',
										 "page_link"=>'subscription_history',
										 "own_data" => isset($_REQUEST['subscription_history_own_data'])?$_REQUEST['subscription_history_own_data']:1,
										 "add" => isset($_REQUEST['subscription_history_add'])?$_REQUEST['subscription_history_add']:0,
										"edit"=>isset($_REQUEST['subscription_history_edit'])?$_REQUEST['subscription_history_edit']:0,
										"view"=>isset($_REQUEST['subscription_history_view'])?$_REQUEST['subscription_history_view']:1,
										"delete"=>isset($_REQUEST['subscription_history_delete'])?$_REQUEST['subscription_history_delete']:0
							  ]
			];
			
		$access_right_staff_member = array();
		$access_right_staff_member['staff_member'] = [
							"staff_member"=>["menu_icone"=>plugins_url('gym-management/assets/images/icon/staff-member.png'),
							           "menu_title"=>'Staff Members',
							           "page_link"=>'staff_member',
									   "own_data" =>isset($_REQUEST['staff_member_own_data'])?$_REQUEST['staff_member_own_data']:1,
									   "add" =>isset($_REQUEST['staff_member_add'])?$_REQUEST['staff_member_add']:0,
										"edit"=>isset($_REQUEST['staff_member_edit'])?$_REQUEST['staff_member_edit']:0,
										"view"=>isset($_REQUEST['staff_member_view'])?$_REQUEST['staff_member_view']:1,
										"delete"=>isset($_REQUEST['staff_member_delete'])?$_REQUEST['staff_member_delete']:0
										],
												
						   "membership"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/membership-type.png'),
						              "menu_title"=>'Membership Type',
						              "page_link"=>'membership',
									 "own_data" => isset($_REQUEST['membership_own_data'])?$_REQUEST['membership_own_data']:0,
									 "add" => isset($_REQUEST['membership_add'])?$_REQUEST['membership_add']:1,
									 "edit"=>isset($_REQUEST['membership_edit'])?$_REQUEST['membership_edit']:1,
									 "view"=>isset($_REQUEST['membership_view'])?$_REQUEST['membership_view']:1,
									 "delete"=>isset($_REQUEST['membership_delete'])?$_REQUEST['membership_delete']:1
						  ],
									  
							"group"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/group.png'),
							        "menu_title"=>'Group',
									"page_link"=>'group',
									 "own_data" => isset($_REQUEST['group_own_data'])?$_REQUEST['group_own_data']:0,
									 "add" => isset($_REQUEST['group_add'])?$_REQUEST['group_add']:1,
									"edit"=>isset($_REQUEST['group_edit'])?$_REQUEST['group_edit']:1,
									"view"=>isset($_REQUEST['group_view'])?$_REQUEST['group_view']:1,
									"delete"=>isset($_REQUEST['group_delete'])?$_REQUEST['group_delete']:1
						  ],
									  
							  "member"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/member.png'),
							            "menu_title"=>'Member',
										"page_link"=>'member',
										"own_data" => isset($_REQUEST['member_own_data'])?$_REQUEST['member_own_data']:0,
										 "add" => isset($_REQUEST['member_add'])?$_REQUEST['member_add']:0,
										 "edit"=>isset($_REQUEST['member_edit'])?$_REQUEST['member_edit']:0,
										"view"=>isset($_REQUEST['member_view'])?$_REQUEST['member_view']:1,
										"delete"=>isset($_REQUEST['member_delete'])?$_REQUEST['member_delete']:0
							  ],
							  
							  "activity"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/activity.png'),
							             "menu_title"=>'Activity',
										 "page_link"=>'activity',
										 "own_data" => isset($_REQUEST['activity_own_data'])?$_REQUEST['activity_own_data']:0,
										 "add" => isset($_REQUEST['activity_add'])?$_REQUEST['activity_add']:1,
										"edit"=>isset($_REQUEST['activity_edit'])?$_REQUEST['activity_edit']:1,
										"view"=>isset($_REQUEST['activity_view'])?$_REQUEST['activity_view']:1,
										"delete"=>isset($_REQUEST['activity_delete'])?$_REQUEST['activity_delete']:1
							  ],
							  "class-schedule"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/class-schedule.png'),
							               "menu_title"=>'Class schedule',
										   "page_link"=>'class-schedule',
										 "own_data" => isset($_REQUEST['class_schedule_own_data'])?$_REQUEST['class_schedule_own_data']:0,
										 "add" => isset($_REQUEST['class_schedule_add'])?$_REQUEST['class_schedule_add']:1,
										"edit"=>isset($_REQUEST['class_schedule_edit'])?$_REQUEST['class_schedule_edit']:1,
										"view"=>isset($_REQUEST['class_schedule_view'])?$_REQUEST['class_schedule_view']:1,
										"delete"=>isset($_REQUEST['class_schedule_delete'])?$_REQUEST['class_schedule_delete']:1
							  ],
							  
							    "attendence"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/attandance.png'),
								         "menu_title"=>'Attendence',
										 "page_link"=>'attendence',
										 "own_data" => isset($_REQUEST['attendence_own_data'])?$_REQUEST['attendence_own_data']:0,
										 "add" => isset($_REQUEST['attendence_add'])?$_REQUEST['attendence_add']:0,
										"edit"=>isset($_REQUEST['attendence_edit'])?$_REQUEST['attendence_edit']:0,
										"view"=>isset($_REQUEST['attendence_view'])?$_REQUEST['attendence_view']:1,
										"delete"=>isset($_REQUEST['attendence_delete'])?$_REQUEST['attendence_delete']:0
							  ],						  
							  
							    "assign-workout"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/assigne-workout.png'),
								         "menu_title"=>'Assigned Workouts',
										 "page_link"=>'assign-workout',
										 "own_data" => isset($_REQUEST['assign_workout_own_data'])?$_REQUEST['assign_workout_own_data']:0,
										 "add" => isset($_REQUEST['assign_workout_add'])?$_REQUEST['assign_workout_add']:1,
										"edit"=>isset($_REQUEST['assign_workout_edit'])?$_REQUEST['assign_workout_edit']:0,
										"view"=>isset($_REQUEST['assign_workout_view'])?$_REQUEST['assign_workout_view']:1,
										"delete"=>isset($_REQUEST['assign_workout_delete'])?$_REQUEST['assign_workout_delete']:1
							  ],
							   "nutrition"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/nutrition-schedule.png'),
							            "menu_title"=>'Nutrition Schedule',
										"page_link"=>'nutrition',
										 "own_data" => isset($_REQUEST['nutrition_own_data'])?$_REQUEST['nutrition_own_data']:0,
										 "add" => isset($_REQUEST['nutrition_add'])?$_REQUEST['nutrition_add']:1,
										"edit"=>isset($_REQUEST['nutrition_edit'])?$_REQUEST['nutrition_edit']:0,
										"view"=>isset($_REQUEST['nutrition_view'])?$_REQUEST['nutrition_view']:1,
										"delete"=>isset($_REQUEST['nutrition_delete'])?$_REQUEST['nutrition_delete']:1
							  ],
							    "workouts"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/workout.png'),
								         "menu_title"=>'Workouts',
										 "page_link"=>'workouts',
										 "own_data" => isset($_REQUEST['workouts_own_data'])?$_REQUEST['workouts_own_data']:0,
										 "add" => isset($_REQUEST['workouts_add'])?$_REQUEST['workouts_add']:1,
										"edit"=>isset($_REQUEST['workouts_edit'])?$_REQUEST['workouts_edit']:0,
										"view"=>isset($_REQUEST['workouts_view'])?$_REQUEST['workouts_view']:1,
										"delete"=>isset($_REQUEST['workouts_delete'])?$_REQUEST['workouts_delete']:0
							  ],
							    "accountant"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/accountant.png'),
								          "menu_title"=>'Accountant',
										  "page_link"=>'accountant',
										 "own_data" => isset($_REQUEST['accountant_own_data'])?$_REQUEST['accountant_own_data']:0,
										 "add" => isset($_REQUEST['accountant_add'])?$_REQUEST['accountant_add']:0,
										"edit"=>isset($_REQUEST['accountant_edit'])?$_REQUEST['accountant_edit']:0,
										"view"=>isset($_REQUEST['accountant_view'])?$_REQUEST['accountant_view']:1,
										"delete"=>isset($_REQUEST['accountant_delete'])?$_REQUEST['accountant_delete']:0
							  ],
							  
							  "membership_payment"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/fee.png'),
							             "menu_title"=>'Fee Payment',
										 "page_link"=>'membership_payment',
										 "own_data" => isset($_REQUEST['membership_payment_own_data'])?$_REQUEST['membership_payment_own_data']:0,
										 "add" => isset($_REQUEST['membership_payment_add'])?$_REQUEST['membership_payment_add']:0,
										"edit"=>isset($_REQUEST['membership_payment_edit'])?$_REQUEST['membership_payment_edit']:0,
										"view"=>isset($_REQUEST['membership_payment_view'])?$_REQUEST['membership_payment_view']:0,
										"delete"=>isset($_REQUEST['membership_payment_delete'])?$_REQUEST['membership_payment_delete']:0
							  ],
							  
							  "payment"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/payment.png'),
							             "menu_title"=>'Payment',
										 "page_link"=>'payment',
										 "own_data" => isset($_REQUEST['payment_own_data'])?$_REQUEST['payment_own_data']:0,
										 "add" => isset($_REQUEST['payment_add'])?$_REQUEST['payment_add']:0,
										"edit"=>isset($_REQUEST['payment_edit'])?$_REQUEST['payment_edit']:0,
										"view"=>isset($_REQUEST['payment_view'])?$_REQUEST['payment_view']:0,
										"delete"=>isset($_REQUEST['payment_delete'])?$_REQUEST['payment_delete']:0
							  ],
							  "product"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/products.png'),
							           "menu_title"=>'Product',
									   "page_link"=>'product',
										 "own_data" => isset($_REQUEST['product_own_data'])?$_REQUEST['product_own_data']:0,
										 "add" => isset($_REQUEST['product_add'])?$_REQUEST['product_add']:1,
										"edit"=>isset($_REQUEST['product_edit'])?$_REQUEST['product_edit']:1,
										"view"=>isset($_REQUEST['product_view'])?$_REQUEST['product_view']:1,
										"delete"=>isset($_REQUEST['product_delete'])?$_REQUEST['product_delete']:1
							  ],
							  "store"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/store.png'),
							              "menu_title"=>'Store',
										  "page_link"=>'store',
										 "own_data" => isset($_REQUEST['store_own_data'])?$_REQUEST['store_own_data']:0,
										 "add" => isset($_REQUEST['store_add'])?$_REQUEST['store_add']:1,
										"edit"=>isset($_REQUEST['store_edit'])?$_REQUEST['store_edit']:0,
										"view"=>isset($_REQUEST['store_view'])?$_REQUEST['store_view']:1,
										"delete"=>isset($_REQUEST['store_delete'])?$_REQUEST['store_delete']:0
							  ],
							  "news_letter"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/newsletter.png'),
							            "menu_title"=>'Newsletter',
										"page_link"=>'news_letter',
										 "own_data" => isset($_REQUEST['news_letter_own_data'])?$_REQUEST['news_letter_own_data']:0,
										 "add" => isset($_REQUEST['news_letter_add'])?$_REQUEST['news_letter_add']:0,
										"edit"=>isset($_REQUEST['news_letter_edit'])?$_REQUEST['news_letter_edit']:0,
										"view"=>isset($_REQUEST['news_letter_view'])?$_REQUEST['news_letter_view']:1,
										"delete"=>isset($_REQUEST['news_letter_delete'])?$_REQUEST['news_letter_delete']:0
							  ],
							  "message"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/message.png'),
							             "menu_title"=>'Message',
										 "page_link"=>'message',
										 "own_data" => isset($_REQUEST['message_own_data'])?$_REQUEST['message_own_data']:1,
										 "add" => isset($_REQUEST['message_add'])?$_REQUEST['message_add']:1,
										"edit"=>isset($_REQUEST['message_edit'])?$_REQUEST['message_edit']:0,
										"view"=>isset($_REQUEST['message_view'])?$_REQUEST['message_view']:1,
										"delete"=>isset($_REQUEST['message_delete'])?$_REQUEST['message_delete']:1
							  ],
							  
							   "notice"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/notice.png'),
							           "menu_title"=>'Notice',
									   "page_link"=>'notice',
										 "own_data" => isset($_REQUEST['notice_own_data'])?$_REQUEST['notice_own_data']:1,
										 "add" => isset($_REQUEST['notice_add'])?$_REQUEST['notice_add']:0,
										"edit"=>isset($_REQUEST['notice_edit'])?$_REQUEST['notice_edit']:0,
										"view"=>isset($_REQUEST['notice_view'])?$_REQUEST['notice_view']:1,
										"delete"=>isset($_REQUEST['notice_delete'])?$_REQUEST['notice_delete']:0
							  ],
							  							  
							   "reservation"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/reservation.png'),							       
								         "menu_title"=>'Reservation',
										 "page_link"=>'reservation',
										 "own_data" => isset($_REQUEST['reservation_own_data'])?$_REQUEST['reservation_own_data']:0,
										 "add" => isset($_REQUEST['reservation_add'])?$_REQUEST['reservation_add']:1,
										"edit"=>isset($_REQUEST['reservation_edit'])?$_REQUEST['reservation_edit']:1,
										"view"=>isset($_REQUEST['reservation_view'])?$_REQUEST['reservation_view']:1,
										"delete"=>isset($_REQUEST['reservation_delete'])?$_REQUEST['reservation_delete']:1
							  ],
							  
							   "account"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/account.png'),
							              "menu_title"=>'Account',
										  "page_link"=>'account',
										 "own_data" => isset($_REQUEST['account_own_data'])?$_REQUEST['account_own_data']:0,
										 "add" => isset($_REQUEST['account_add'])?$_REQUEST['account_add']:0,
										"edit"=>isset($_REQUEST['account_edit'])?$_REQUEST['account_edit']:0,
										"view"=>isset($_REQUEST['account_view'])?$_REQUEST['account_view']:1,
										"delete"=>isset($_REQUEST['account_delete'])?$_REQUEST['account_delete']:0
							  ],
							   "subscription_history"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/subscription_history.png'),
							             "menu_title"=>'Subscription History',
										 "page_link"=>'subscription_history',
										 "own_data" => isset($_REQUEST['subscription_history_own_data'])?$_REQUEST['subscription_history_own_data']:0,
										 "add" => isset($_REQUEST['subscription_history_add'])?$_REQUEST['subscription_history_add']:0,
										"edit"=>isset($_REQUEST['subscription_history_edit'])?$_REQUEST['subscription_history_edit']:0,
										"view"=>isset($_REQUEST['subscription_history_view'])?$_REQUEST['subscription_history_view']:1,
										"delete"=>isset($_REQUEST['subscription_history_delete'])?$_REQUEST['subscription_history_delete']:0
							  ]
			];	
				
		$access_right_accountant = array();
		$access_right_accountant['accountant'] = [
							"staff_member"=>["menu_icone"=>plugins_url('gym-management/assets/images/icon/staff-member.png'),
							           "menu_title"=>'Staff Members',
							           "page_link"=>'staff_member',
									   "own_data" =>isset($_REQUEST['staff_member_own_data'])?$_REQUEST['staff_member_own_data']:0,
									   "add" =>isset($_REQUEST['staff_member_add'])?$_REQUEST['staff_member_add']:0,
										"edit"=>isset($_REQUEST['staff_member_edit'])?$_REQUEST['staff_member_edit']:0,
										"view"=>isset($_REQUEST['staff_member_view'])?$_REQUEST['staff_member_view']:1,
										"delete"=>isset($_REQUEST['staff_member_delete'])?$_REQUEST['staff_member_delete']:0
										],
												
						   "membership"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/membership-type.png'),
						              "menu_title"=>'Membership Type',
						              "page_link"=>'membership',
									 "own_data" => isset($_REQUEST['membership_own_data'])?$_REQUEST['membership_own_data']:0,
									 "add" => isset($_REQUEST['membership_add'])?$_REQUEST['membership_add']:0,
									 "edit"=>isset($_REQUEST['membership_edit'])?$_REQUEST['membership_edit']:0,
									 "view"=>isset($_REQUEST['membership_view'])?$_REQUEST['membership_view']:0,
									 "delete"=>isset($_REQUEST['membership_delete'])?$_REQUEST['membership_delete']:0
						  ],
									  
							"group"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/group.png'),
							        "menu_title"=>'Group',
									"page_link"=>'group',
									 "own_data" => isset($_REQUEST['group_own_data'])?$_REQUEST['group_own_data']:0,
									 "add" => isset($_REQUEST['group_add'])?$_REQUEST['group_add']:0,
									"edit"=>isset($_REQUEST['group_edit'])?$_REQUEST['group_edit']:0,
									"view"=>isset($_REQUEST['group_view'])?$_REQUEST['group_view']:0,
									"delete"=>isset($_REQUEST['group_delete'])?$_REQUEST['group_delete']:0
						  ],
									  
							  "member"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/member.png'),
							            "menu_title"=>'Member',
										"page_link"=>'member',
										"own_data" => isset($_REQUEST['member_own_data'])?$_REQUEST['member_own_data']:0,
										 "add" => isset($_REQUEST['member_add'])?$_REQUEST['member_add']:0,
										 "edit"=>isset($_REQUEST['member_edit'])?$_REQUEST['member_edit']:0,
										"view"=>isset($_REQUEST['member_view'])?$_REQUEST['member_view']:1,
										"delete"=>isset($_REQUEST['member_delete'])?$_REQUEST['member_delete']:0
							  ],
							  
							  "activity"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/activity.png'),
							             "menu_title"=>'Activity',
										 "page_link"=>'activity',
										 "own_data" => isset($_REQUEST['activity_own_data'])?$_REQUEST['activity_own_data']:0,
										 "add" => isset($_REQUEST['activity_add'])?$_REQUEST['activity_add']:0,
										"edit"=>isset($_REQUEST['activity_edit'])?$_REQUEST['activity_edit']:0,
										"view"=>isset($_REQUEST['activity_view'])?$_REQUEST['activity_view']:0,
										"delete"=>isset($_REQUEST['activity_delete'])?$_REQUEST['activity_delete']:0
							  ],
							  "class-schedule"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/class-schedule.png'),
							               "menu_title"=>'Class schedule',
										   "page_link"=>'class-schedule',
										 "own_data" => isset($_REQUEST['class_schedule_own_data'])?$_REQUEST['class_schedule_own_data']:0,
										 "add" => isset($_REQUEST['class_schedule_add'])?$_REQUEST['class_schedule_add']:0,
										"edit"=>isset($_REQUEST['class_schedule_edit'])?$_REQUEST['class_schedule_edit']:0,
										"view"=>isset($_REQUEST['class_schedule_view'])?$_REQUEST['class_schedule_view']:0,
										"delete"=>isset($_REQUEST['class_schedule_delete'])?$_REQUEST['class_schedule_delete']:0
							  ],
							  
							    "attendence"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/attandance.png'),
								         "menu_title"=>'Attendence',
										 "page_link"=>'attendence',
										 "own_data" => isset($_REQUEST['attendence_own_data'])?$_REQUEST['attendence_own_data']:0,
										 "add" => isset($_REQUEST['attendence_add'])?$_REQUEST['attendence_add']:0,
										"edit"=>isset($_REQUEST['attendence_edit'])?$_REQUEST['attendence_edit']:0,
										"view"=>isset($_REQUEST['attendence_view'])?$_REQUEST['attendence_view']:0,
										"delete"=>isset($_REQUEST['attendence_delete'])?$_REQUEST['attendence_delete']:0
							  ],						  
							  
							    "assign-workout"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/assigne-workout.png'),
								         "menu_title"=>'Assigned Workouts',
										 "page_link"=>'assign-workout',
										 "own_data" => isset($_REQUEST['assign_workout_own_data'])?$_REQUEST['assign_workout_own_data']:0,
										 "add" => isset($_REQUEST['assign_workout_add'])?$_REQUEST['assign_workout_add']:0,
										"edit"=>isset($_REQUEST['assign_workout_edit'])?$_REQUEST['assign_workout_edit']:0,
										"view"=>isset($_REQUEST['assign_workout_view'])?$_REQUEST['assign_workout_view']:0,
										"delete"=>isset($_REQUEST['assign_workout_delete'])?$_REQUEST['assign_workout_delete']:0
							  ],
							  "nutrition"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/nutrition-schedule.png'),
							            "menu_title"=>'Nutrition Schedule',
										"page_link"=>'nutrition',
										 "own_data" => isset($_REQUEST['nutrition_own_data'])?$_REQUEST['nutrition_own_data']:0,
										 "add" => isset($_REQUEST['nutrition_add'])?$_REQUEST['nutrition_add']:0,
										"edit"=>isset($_REQUEST['nutrition_edit'])?$_REQUEST['nutrition_edit']:0,
										"view"=>isset($_REQUEST['nutrition_view'])?$_REQUEST['nutrition_view']:0,
										"delete"=>isset($_REQUEST['nutrition_delete'])?$_REQUEST['nutrition_delete']:0
							  ],
							    "workouts"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/workout.png'),
								         "menu_title"=>'Workouts',
										 "page_link"=>'workouts',
										 "own_data" => isset($_REQUEST['workouts_own_data'])?$_REQUEST['workouts_own_data']:0,
										 "add" => isset($_REQUEST['workouts_add'])?$_REQUEST['workouts_add']:0,
										"edit"=>isset($_REQUEST['workouts_edit'])?$_REQUEST['workouts_edit']:0,
										"view"=>isset($_REQUEST['workouts_view'])?$_REQUEST['workouts_view']:0,
										"delete"=>isset($_REQUEST['workouts_delete'])?$_REQUEST['workouts_delete']:0
							  ],
							    "accountant"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/accountant.png'),
								          "menu_title"=>'Accountant',
										  "page_link"=>'accountant',
										 "own_data" => isset($_REQUEST['accountant_own_data'])?$_REQUEST['accountant_own_data']:1,
										 "add" => isset($_REQUEST['accountant_add'])?$_REQUEST['accountant_add']:0,
										"edit"=>isset($_REQUEST['accountant_edit'])?$_REQUEST['accountant_edit']:0,
										"view"=>isset($_REQUEST['accountant_view'])?$_REQUEST['accountant_view']:1,
										"delete"=>isset($_REQUEST['accountant_delete'])?$_REQUEST['accountant_delete']:0
							  ],
							  
							  "membership_payment"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/fee.png'),
							             "menu_title"=>'Fee Payment',
										 "page_link"=>'membership_payment',
										 "own_data" => isset($_REQUEST['membership_payment_own_data'])?$_REQUEST['membership_payment_own_data']:0,
										 "add" => isset($_REQUEST['membership_payment_add'])?$_REQUEST['membership_payment_add']:0,
										"edit"=>isset($_REQUEST['membership_payment_edit'])?$_REQUEST['membership_payment_edit']:0,
										"view"=>isset($_REQUEST['membership_payment_view'])?$_REQUEST['membership_payment_view']:1,
										"delete"=>isset($_REQUEST['membership_payment_delete'])?$_REQUEST['membership_payment_delete']:0
							  ],
							  
							  "payment"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/payment.png'),
							             "menu_title"=>'Payment',
										 "page_link"=>'payment',
										 "own_data" => isset($_REQUEST['payment_own_data'])?$_REQUEST['payment_own_data']:0,
										 "add" => isset($_REQUEST['payment_add'])?$_REQUEST['payment_add']:1,
										"edit"=>isset($_REQUEST['payment_edit'])?$_REQUEST['payment_edit']:1,
										"view"=>isset($_REQUEST['payment_view'])?$_REQUEST['payment_view']:1,
										"delete"=>isset($_REQUEST['payment_delete'])?$_REQUEST['payment_delete']:1
							  ],
							  "product"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/products.png'),
							           "menu_title"=>'Product',
									   "page_link"=>'product',
										 "own_data" => isset($_REQUEST['product_own_data'])?$_REQUEST['product_own_data']:0,
										 "add" => isset($_REQUEST['product_add'])?$_REQUEST['product_add']:1,
										"edit"=>isset($_REQUEST['product_edit'])?$_REQUEST['product_edit']:1,
										"view"=>isset($_REQUEST['product_view'])?$_REQUEST['product_view']:1,
										"delete"=>isset($_REQUEST['product_delete'])?$_REQUEST['product_delete']:1
							  ],
							  "store"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/store.png'),
							              "menu_title"=>'Store',
										  "page_link"=>'store',
										 "own_data" => isset($_REQUEST['store_own_data'])?$_REQUEST['store_own_data']:0,
										 "add" => isset($_REQUEST['store_add'])?$_REQUEST['store_add']:1,
										"edit"=>isset($_REQUEST['store_edit'])?$_REQUEST['store_edit']:0,
										"view"=>isset($_REQUEST['store_view'])?$_REQUEST['store_view']:1,
										"delete"=>isset($_REQUEST['store_delete'])?$_REQUEST['store_delete']:0
							  ],
							  "news_letter"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/newsletter.png'),
							            "menu_title"=>'Newsletter',
										"page_link"=>'news_letter',
										 "own_data" => isset($_REQUEST['news_letter_own_data'])?$_REQUEST['news_letter_own_data']:0,
										 "add" => isset($_REQUEST['news_letter_add'])?$_REQUEST['news_letter_add']:0,
										"edit"=>isset($_REQUEST['news_letter_edit'])?$_REQUEST['news_letter_edit']:0,
										"view"=>isset($_REQUEST['news_letter_view'])?$_REQUEST['news_letter_view']:0,
										"delete"=>isset($_REQUEST['news_letter_delete'])?$_REQUEST['news_letter_delete']:0
							  ],
							  "message"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/message.png'),
							             "menu_title"=>'Message',
										 "page_link"=>'message',
										 "own_data" => isset($_REQUEST['message_own_data'])?$_REQUEST['message_own_data']:1,
										 "add" => isset($_REQUEST['message_add'])?$_REQUEST['message_add']:1,
										"edit"=>isset($_REQUEST['message_edit'])?$_REQUEST['message_edit']:0,
										"view"=>isset($_REQUEST['message_view'])?$_REQUEST['message_view']:1,
										"delete"=>isset($_REQUEST['message_delete'])?$_REQUEST['message_delete']:1
							  ],
							  
							   "notice"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/notice.png'),
							           "menu_title"=>'Notice',
									   "page_link"=>'notice',
										 "own_data" => isset($_REQUEST['notice_own_data'])?$_REQUEST['notice_own_data']:1,
										 "add" => isset($_REQUEST['notice_add'])?$_REQUEST['notice_add']:0,
										"edit"=>isset($_REQUEST['notice_edit'])?$_REQUEST['notice_edit']:0,
										"view"=>isset($_REQUEST['notice_view'])?$_REQUEST['notice_view']:1,
										"delete"=>isset($_REQUEST['notice_delete'])?$_REQUEST['notice_delete']:0
							  ],
							  
							   "reservation"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/reservation.png'),							       
								         "menu_title"=>'Reservation',
										 "page_link"=>'reservation',
										 "own_data" => isset($_REQUEST['reservation_own_data'])?$_REQUEST['reservation_own_data']:0,
										 "add" => isset($_REQUEST['reservation_add'])?$_REQUEST['reservation_add']:0,
										"edit"=>isset($_REQUEST['reservation_edit'])?$_REQUEST['reservation_edit']:0,
										"view"=>isset($_REQUEST['reservation_view'])?$_REQUEST['reservation_view']:0,
										"delete"=>isset($_REQUEST['reservation_delete'])?$_REQUEST['reservation_delete']:0
							  ],
							  
							   "account"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/account.png'),
							              "menu_title"=>'Account',
										  "page_link"=>'account',
										 "own_data" => isset($_REQUEST['account_own_data'])?$_REQUEST['account_own_data']:0,
										 "add" => isset($_REQUEST['account_add'])?$_REQUEST['account_add']:0,
										"edit"=>isset($_REQUEST['account_edit'])?$_REQUEST['account_edit']:0,
										"view"=>isset($_REQUEST['account_view'])?$_REQUEST['account_view']:1,
										"delete"=>isset($_REQUEST['account_delete'])?$_REQUEST['account_delete']:0
							  ],
							   "subscription_history"=>['menu_icone'=>plugins_url( 'gym-management/assets/images/icon/subscription_history.png'),
							             "menu_title"=>'Subscription History',
										 "page_link"=>'subscription_history',
										 "own_data" => isset($_REQUEST['subscription_history_own_data'])?$_REQUEST['subscription_history_own_data']:0,
										 "add" => isset($_REQUEST['subscription_history_add'])?$_REQUEST['subscription_history_add']:0,
										"edit"=>isset($_REQUEST['subscription_history_edit'])?$_REQUEST['subscription_history_edit']:0,
										"view"=>isset($_REQUEST['subscription_history_view'])?$_REQUEST['subscription_history_view']:1,
										"delete"=>isset($_REQUEST['subscription_history_delete'])?$_REQUEST['subscription_history_delete']:0
							  ]
			];	
			
		$options=array("gmgt_system_name"=> __( 'Gym Management System' ,'gym_mgt'),
					"gmgt_staring_year"=>"2018",
					"gmgt_gym_address"=>"A 206, Shapath Hexa, S G Road",
					"gmgt_contact_number"=>"9999999999",
					"gmgt_alternate_contact_number"=>"8888888888",
					"gmgt_contry"=>"India",
					"gmgt_email"=>get_option('admin_email'),
					"gmgt_datepicker_format"=>'yy/mm/dd',
					"gmgt_system_logo"=>plugins_url( 'gym-management/assets/images/Thumbnail-img.png' ),
					"biglogo"=>plugins_url( 'gym-management/assets/images/WP_gym_logo.png' ),
					"gmgt_gym_background_image"=>plugins_url('gym-management/assets/images/gym-background.png' ),
					"gmgt_instructor_thumb"=>plugins_url( 'gym-management/assets/images/useriamge/instructor.png' ),
					"gmgt_member_thumb"=>plugins_url( 'gym-management/assets/images/useriamge/member.png' ),
					
					"gmgt_assistant_thumb"=>plugins_url( 'gym-management/assets/images/useriamge/assistant.png' ),
					
					"gmgt_accountant_thumb"=>plugins_url( 'gym-management/assets/images/useriamge/accountant.png' ),
					
					"gmgt_mailchimp_api"=>"",
					"gmgt_sms_service"=>"",
					"gmgt_sms_service_enable"=> 0,					
					"gmgt_clickatell_sms_service"=>array(),
					"gmgt_twillo_sms_service"=>array(),
					"gmgt_weight_unit"=>'KG',
					"gmgt_height_unit"=>'Centimeter',
					"gmgt_chest_unit"=>'Inches',
					"gmgt_waist_unit"=>'Inches',
					"gmgt_thigh_unit"=>'Inches',
					"gmgt_arms_unit"=>'Inches',
					"gmgt_fat_unit"=>'Percentage',
					"gmgt_paypal_email"=>'',
					"gym_enable_sandbox"=>'yes',
					"pm_payment_method"=>'paypal',
					"gmgt_currency_code" => 'USD',
					"gym_enable_membership_alert_message" => 'yes',
					"gmgt_reminder_before_days" => '20',
					"gmgt_bank_holder_name"=>"",
					"gmgt_bank_name"=>"",
					"gmgt_bank_acount_number"=>"",
					"gmgt_bank_ifsc_code"=>"",
					"gmgt_mailchimp_api"=>"",
					"gym_enable_past_attendance"=>"no",
					"gym_enable_datepicker_privious_date"=>"no",
					"gym_frontend_class_booking"=>"yes",
					
					"gmgt_access_right_member"=>$access_right_member,				
					"gmgt_access_right_staff_member"=>$access_right_staff_member,				
					"gmgt_access_right_accountant"=>$access_right_accountant,		
					
					"gym_reminder_message" => 'Hello [GMGT_MEMBERNAME],
					
 Your [GMGT_MEMBERSHIP]  started at [GMGT_STARTDATE] and it will be expire on [GMGT_ENDDATE] .
	  
Regard
[GMGT_GYM_NAME]',
	  
	  "gmgt_reminder_subject" => 'Membership expire reminder at [GMGT_GYM_NAME]',
	  
	               'registration_title'=>'You are successfully registered at [GMGT_GYM_NAME]',
					'registration_mailtemplate'=>'Dear [GMGT_MEMBERNAME] ,
					
        You are successfully registered at [GMGT_GYM_NAME] .Your member id is [GMGT_MEMBERID] .Your  Membership name is [GMGT_MEMBERSHIP] .Your Membership start date is [GMGT_STARTDATE] .Your Membership end date is [GMGT_ENDDATE] .You can access your account after admin approval.

Regards From [GMGT_GYM_NAME].',

                   'Member_Approved_Template_Subject'=>'You profile has been approved by admin at [GMGT_GYM_NAME]',
					'Member_Approved_Template'=>'Dear Member Name,
					
         You are successfully registered at [GMGT_GYM_NAME].You profile has been approved by admin and you can sign in using this link. [GMGT_LOGIN_LINK] 
 
Regards From [GMGT_GYM_NAME].',

                    'Add_Other_User_in_System_Subject'=>'Your have been assigned role of [GMGT_ROLE_NAME] in [GMGT_GYM_NAME] ',
					'Add_Other_User_in_System_Template'=>'Dear [GMGT_USERNAME],
					
         You are Added by admin of [GMGT_GYM_NAME].Your have been assigned role of [GMGT_ROLE_NAME] in [GMGT_GYM_NAME]. You can access system using your username and password.  You can signin using this link.[GMGT_LOGIN_LINK] 
UserName : [GMGT_Username].
Password : [GMGT_PASSWORD].
Regards From [GMGT_GYM_NAME].',

                    'Add_Notice_Subject'=>'New Notice from [GMGT_USERNAME] at [GMGT_GYM_NAME] ',
					'Add_Notice_Template'=>'Dear [GMGT_USERNAME] ,
					
         Here is the new Notice from  [GMGT_MEMBERNAME].
Title : [GMGT_NOTICE_TITLE].
Notice For: [GMGT_NOTICE_FOR].
Notice Start Date : Notice [GMGT_STARTDATE].
Notice End Date : Notice [GMGT_ENDDATE].
Description : Notice [GMGT_COMMENT].
View Notice Click [GMGT_NOTICE_LINK]

Regards From [GMGT_GYM_NAME] .',

                    'Member_Added_In_Group_subject'=>'You are added in [GMGT_GROUPNAME] at [GMGT_GYM_NAME] ',
					'Member_Added_In_Group_Template'=>'Dear [GMGT_USERNAME],
					
         You are added in [GMGT_GROUPNAME] . 
     
Regards From [GMGT_GYM_NAME] .',

                    'Assign_Workouts_Subject'=>'New workouts assigned to you at [GMGT_GYM_NAME] ',
					'Assign_Workouts_Template'=>'Dear [GMGT_MEMBERNAME],
					
         You have assigned new workouts for [GMGT_STARTDATE] To [GMGT_ENDDATE] .We have also attached your schedule.For View  Workout  [GMGT_PAGE_LINK]

Regards From [GMGT_GYM_NAME] .',

                    'Add_Reservation_Subject'=>' [GMGT_EVENT_PLACE] have been Successfully reserved for you for [GMGT_EVENT_NAME] on [GMGT_EVENT_DATE] And [GMGT_START_TIME] ',
					'Add_Reservation_Template'=>'Dear [GMGT_STAFF_MEMBERNAME],
					
        [GMGT_EVENT_PLACE] has been successfully booked for you. This place booked for [GMGT_EVENT_NAME] on [GMGT_EVENT_DATE] And [GMGT_START_TIME] . 
   
        Event Name: [GMGT_EVENT_NAME].
        Event Date : [GMGT_EVENT_DATE].
        Event Place: [GMGT_EVENT_PLACE].
        Event Start Time: [GMGT_START_TIME]. 
        Event EndTime: [GMGT_END_TIME].
[GMGT_PAGE_LINK] 
		
Regards From [GMGT_GYM_NAME] .',

                    'Assign_Nutrition_Schedule_Subject'=>'New Nutrition Schedule assigned to you at [GMGT_GYM_NAME] ',
					'Assign_Nutrition_Schedule_Template'=>'Dear [GMGT_MEMBERNAME],
					
          You have assigned new nutrition schedule for [GMGT_STARTDATE] To [GMGT_ENDDATE]. We have also attached your schedule.For View Nutrition  [GMGT_PAGE_LINK]

Regards From [GMGT_GYM_NAME].',

                    'Submit_Workouts_Subject'=>'[GMGT_STAFF_MEMBERNAME]  has updated daily workout log',
					'Submit_Workouts_Template'=>'Dear [GMGT_STAFF_MEMBERNAME] ,

        I have completed my workout of [GMGT_DAY_NAME] on [GMGT_DATE] . Attached details of my workouts. 
		 
Regards From [GMGT_GYM_NAME].',

                    'sell_product_subject'=>'You have purchased new product from  [GMGT_GYM_NAME]',
					'sell_product_template'=>'Dear [GMGT_USERNAME], 
             
             Your have purchased products.  You can check the product  Invoice attached here. 

Regards From [GMGT_GYM_NAME] .',

                    'generate_invoice_subject'=>'Your have a new invoice from [GMGT_GYM_NAME]',
					'generate_invoice_template'=>'Dear [GMGT_USERNAME],

        Your have a new Fees invoice. You can check the invoice attached here. For payment click [GMGT_PAYMENT_LINK]
 
Regards From [GMGT_GYM_NAME].',

                    'add_income_subject'=>'Your have a new Payment Invoice raised by [GMGT_ROLE_NAME] at [GMGT_GYM_NAME]',
					'add_income_template'=>'Dear [GMGT_USERNAME],

        Your have a new Payment Invoice raised by Admin. You can check the Invoice attached here.
 
Regards From [GMGT_GYM_NAME].',

                    'payment_received_against_invoice_subject'=>'Your have successfully paid your invoice at [GMGT_GYM_NAME]',
					'payment_received_against_invoice_template'=>'Dear [GMGT_USERNAME],

        Your have successfully paid your invoice.  You can check the invoice attached here.
 
Regards From [GMGT_GYM_NAME].',

                    'message_received_subject'=>'You have received new message from [GMGT_SENDER_NAME]  at [GMGT_GYM_NAME]',
					'message_received_template'=>'Dear [GMGT_RECEIVER_NAME],

         You have received new message from [GMGT_SENDER_NAME]. [GMGT_MESSAGE_CONTENT].
 
Regards From [GMGT_GYM_NAME].',

		);
		return $options;
}
add_action('admin_init','MJ_gmgt_general_setting');	
//ADD GENERAL SETTINGS OPTION FUNCTION
function MJ_gmgt_general_setting()
{
	$options=MJ_gmgt_option();
	foreach($options as $key=>$val)
	{
		add_option($key,$val); 
		
	}
}
//GET ALL SCRIPT PAGE IN ADMIN SIDE FUNCTION
function MJ_gmgt_call_script_page()
{
	$page_array = array('gmgt_system','gmgt_membership_type','gmgt_group','gmgt_staff','gmgt_accountant','gmgt_class','gmgt_member',
			'gmgt_product','gmgt_reservation','gmgt_attendence','gmgt_taxes','gmgt_fees_payment','gmgt_payment','Gmgt_message','gmgt_newsletter','gmgt_activity',
			'gmgt_notice','gmgt_workouttype','gmgt_workout','gmgt_store','gmgt_nutrition','gmgt_report','gmgt_mail_template','gmgt_gnrl_settings','gmgt_access_right','gmgt_alumni','gmgt_prospect','gmgt_setup');
	return  $page_array;
}
//ADMIN SIDE CSS AND JS ADD FUNCTION
function MJ_gmgt_change_adminbar_css($hook)
{	
	$current_page = $_REQUEST['page'];
	$page_array = MJ_gmgt_call_script_page();
	if(in_array($current_page,$page_array))
    {				
				wp_enqueue_style( 'accordian-jquery-ui-css', plugins_url( '/assets/accordian/jquery-ui.css', __FILE__) );		
				wp_enqueue_script('accordian-jquery-ui', plugins_url( '/assets/accordian/jquery-ui.js',__FILE__ ));
			
				wp_enqueue_style( 'gmgt  -calender-css', plugins_url( '/assets/css/fullcalendar.css', __FILE__) );
				wp_enqueue_style( 'gmgt  -datatable-css', plugins_url( '/assets/css/dataTables.css', __FILE__) );
				wp_enqueue_style( 'gmgt-dataTables.responsive-css', plugins_url( '/assets/css/dataTables.responsive.css', __FILE__) );
				
				wp_enqueue_style( 'gmgt  -style-css', plugins_url( '/assets/css/style.css', __FILE__) );
				wp_enqueue_style( 'gmgt  -dashboard-css', plugins_url( '/assets/css/dashboard.css', __FILE__) );
				wp_enqueue_style( 'gmgt  -popup-css', plugins_url( '/assets/css/popup.css', __FILE__) );
				wp_enqueue_style( 'gmgt  -custom-css', plugins_url( '/assets/css/custom.css', __FILE__) );
				wp_enqueue_style( 'gmgt-select2-css', plugins_url( '/lib/select2-3.5.3/select2.css', __FILE__) );
				
				wp_enqueue_script('gmgt-select2', plugins_url( '/lib/select2-3.5.3/select2.min.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
				
				wp_enqueue_script('gmgt  -calender_moment', plugins_url( '/assets/js/moment.min.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
				wp_enqueue_script('gmgt  -calender', plugins_url( '/assets/js/fullcalendar.min.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
				wp_enqueue_script('gmgt  -datatable', plugins_url( '/assets/js/jquery.dataTables.min.js',__FILE__ ), array( 'jquery' ), '4.1.1', true);
				$lancode=get_locale();
				$code=substr($lancode,0,2);
				wp_enqueue_script('gmgt-calender-'.$code.'', plugins_url( '/assets/js/calendar-lang/'.$code.'.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
				wp_enqueue_script('gmgt  -datatable-tools', plugins_url( '/assets/js/dataTables.tableTools.min.js',__FILE__ ), array( 'jquery' ), '4.1.1', true);
				wp_enqueue_script('gmgt  -datatable-editor', plugins_url( '/assets/js/dataTables.editor.min.js',__FILE__ ), array( 'jquery' ), '4.1.1', true);	
				wp_enqueue_script('gmgt-dataTables.responsive-js', plugins_url( '/assets/js/dataTables.responsive.js',__FILE__ ), array( 'jquery' ), '4.1.1', true);	
				wp_enqueue_script('gmgt-customjs', plugins_url( '/assets/js/gmgt_custom.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
				wp_enqueue_script('gmgt-popup', plugins_url( '/assets/js/popup.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
				
				//popup file alert msg languages translation//				
				wp_localize_script('gmgt-popup', 'language_translate', array(
						'product_out_of_stock_alert' => __( 'Product out of stock', 'gym_mgt' ),
						'select_one_membership_alert' => __( 'please select at least one member type', 'gym_mgt' ),
						'membership_member_limit_alert' => __( 'Membership member limit is full', 'gym_mgt' ),
						'sets_lable' => __( 'Sets', 'gym_mgt' ),
						'reps_lable' => __( 'Reps', 'gym_mgt' ),
						'kg_lable' => __( 'KG', 'gym_mgt' ),
						'rest_time_lable' => __( 'Rest Time', 'gym_mgt' ),
						'min_lable' => __( 'Min', 'gym_mgt' ),
						'assigned_workout_lable' => __( 'Assign Workout', 'gym_mgt' ),
						'days_lable' => __( 'Days', 'gym_mgt' ),
						'nutrition_schedule_details_lable' => __( 'Nutrition Schedule Details', 'gym_mgt' ),
						'dinner_lable' => __( 'Dinner Nutrition', 'gym_mgt' ),
						'breakfast_lable' => __( 'Break Fast Nutrition', 'gym_mgt' ),
						'lunch_lable' => __( 'Lunch Nutrition', 'gym_mgt' )						
					)
				);
				//add page in ajax that use localize ajax page
				wp_localize_script( 'gmgt-popup', 'gmgt', array( 'ajax' => admin_url( 'admin-ajax.php' ) ) );
			 	wp_enqueue_script('jquery');
			 	wp_enqueue_media();
		       	wp_enqueue_script('thickbox');
		       	wp_enqueue_style('thickbox');
		 
			 	wp_enqueue_script('gmgt -image-upload', plugins_url( '/assets/js/image-upload.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
			 
				//image upload file alert msg languages translation				
				wp_localize_script('gmgt -image-upload', 'language_translate1', array(
						'allow_file_alert' => __( 'Only (JPEG,JPG,GIF,PNG,BMP) File is allowed', 'gym_mgt' )						
					)
				);
				
				wp_enqueue_style( 'gmgt  -bootstrap-css', plugins_url( '/assets/css/bootstrap.min.css', __FILE__) );
				wp_enqueue_style( 'gmgt  -bootstrap-multiselect-css', plugins_url( '/assets/css/bootstrap-multiselect.css', __FILE__) );
			
				wp_enqueue_style( 'gmgt  -bootstrap-timepicker-css', plugins_url( '/assets/css/datepicker.min.css', __FILE__) );
				
			 	wp_enqueue_style( 'gmgt  -font-awesome-css', plugins_url( '/assets/css/font-awesome.min.css', __FILE__) );
			 	wp_enqueue_style( 'gmgt  -white-css', plugins_url( '/assets/css/white.css', __FILE__) );
			 	wp_enqueue_style( 'gmgt-gymmgt-min-css', plugins_url( '/assets/css/gymmgt.min.css', __FILE__) );
			 	wp_enqueue_style( 'gmgt-sweetalert-css', plugins_url( '/assets/css/sweetalert.css', __FILE__) );
				if (is_rtl())
				{
					wp_enqueue_style( 'gmgt-bootstrap-rtl-css', plugins_url( '/assets/css/bootstrap-rtl.min.css', __FILE__) );
				}
				wp_enqueue_style( 'gmgt-gym-responsive-css', plugins_url( '/assets/css/gym-responsive.css', __FILE__) );
			  
			 	wp_enqueue_script('gmgt-bootstrap-js', plugins_url( '/assets/js/bootstrap.min.js', __FILE__ ) );
			 	wp_enqueue_script('gmgt-bootstrap-multiselect-js', plugins_url( '/assets/js/bootstrap-multiselect.js', __FILE__ ) );
				
			 	wp_enqueue_script('gmgt-bootstrap-timepicker-js', plugins_url( '/assets/js/bootstrap-datepicker.js', __FILE__ ) );
			 	wp_enqueue_script('gmgt-gym-js', plugins_url( '/assets/js/gymjs.js', __FILE__ ) );
				wp_enqueue_script('gmgt-slider-js', plugins_url( '/assets/js/jssor.slider.mini.js', __FILE__ ) );
				wp_enqueue_script('gmgt-sweetalert-dev-js', plugins_url( '/assets/js/sweetalert-dev.js', __FILE__ ) );
			 	//Validation style And Script
			 	//validation lib
			 	wp_enqueue_style( 'wcwm-validate-css', plugins_url( '/lib/validationEngine/css/validationEngine.jquery.css', __FILE__) );	 	
			 	wp_register_script( 'jquery-validationEngine-'.$code.'', plugins_url( '/lib/validationEngine/js/languages/jquery.validationEngine-'.$code.'.js', __FILE__), array( 'jquery' ) );
			 	wp_enqueue_script( 'jquery-validationEngine-'.$code.'' );
			 	wp_register_script( 'jquery-validationEngine', plugins_url( '/lib/validationEngine/js/jquery.validationEngine.js', __FILE__), array( 'jquery' ) );
			 	wp_enqueue_script( 'jquery-validationEngine' );
			    wp_enqueue_script('gmgt-gmgt_custom_confilict_obj-js', plugins_url( '/assets/js/gmgt_custom_confilict_obj.js', __FILE__ ) );			 	
	}
		
}
	if(isset($_REQUEST['page']))
	add_action( 'admin_enqueue_scripts', 'MJ_gmgt_change_adminbar_css' );
}

//REMOVE OL STYLE IN THEMAE FUNCTION
function MJ_gmgt_remove_all_theme_styles()
{
	global $wp_styles;
	$wp_styles->queue = array();
}
//FRONTEND SIDE CHECK USER DASHBORD FUNCTION
if(isset($_REQUEST['dashboard']) && $_REQUEST['dashboard'] == 'user')
{
	add_action('wp_print_styles', 'MJ_gmgt_remove_all_theme_styles', 100);
}
//LOAD SCRIPT FUNCTION
function MJ_gmgt_load_script1()
{
	if(isset($_REQUEST['dashboard']) && $_REQUEST['dashboard'] == 'user')
	{
		wp_register_script('gmgt  -popup-front', plugins_url( 'assets/js/popup.js', __FILE__ ), array( 'jquery' ));
		wp_enqueue_script('gmgt  -popup-front');
		//popup file alert msg languages translation//				
		wp_localize_script('gmgt  -popup-front', 'language_translate', array(
				'product_out_of_stock_alert' => __( 'Product out of stock', 'gym_mgt' ),
				'select_one_membership_alert' => __( 'please select at least one member type', 'gym_mgt' ),
				'membership_member_limit_alert' => __( 'Membership member limit is full', 'gym_mgt' ),
				'sets_lable' => __( 'Sets', 'gym_mgt' ),
				'reps_lable' => __( 'Reps', 'gym_mgt' ),
				'kg_lable' => __( 'KG', 'gym_mgt' ),
				'rest_time_lable' => __( 'Rest Time', 'gym_mgt' ),
				'min_lable' => __( 'Min', 'gym_mgt' ),
				'assigned_workout_lable' => __( 'Assign Workout', 'gym_mgt' ),
				'days_lable' => __( 'Days', 'gym_mgt' ),
				'nutrition_schedule_details_lable' => __( 'Nutrition Schedule Details', 'gym_mgt' ),
				'dinner_lable' => __( 'Dinner Nutrition', 'gym_mgt' ),
				'breakfast_lable' => __( 'Break Fast Nutrition', 'gym_mgt' ),
				'lunch_lable' => __( 'Lunch Nutrition', 'gym_mgt' )						
			)
		);
		wp_localize_script( 'gmgt  -popup-front', 'gmgt  ', array( 'ajax' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script('jquery');	 
	}
	
	wp_enqueue_style( 'gmgt-style-css', plugins_url( '/assets/css/style.css', __FILE__) );
}
//DOMAIN NAME LOAD FUNCTION
function MJ_gmgt_domain_load()
{
	load_plugin_textdomain( 'gym_mgt', false, dirname( plugin_basename( __FILE__ ) ). '/languages/' );
}
//INSTALL LOGIN PAGE
function MJ_gmgt_install_login_page()
{
	if ( !get_option('gmgt_login_page') )
	{
		$curr_page = array(
				'post_title' => __('Gym Management Login Page', 'gym_mgt'),
				'post_content' => '[gmgt_login]',
				'post_status' => 'publish',
				'post_type' => 'page',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_category' => array(1),
				'post_parent' => 0 );		

			$curr_created = wp_insert_post( $curr_page );
			update_option( 'gmgt_login_page', $curr_created );
	}
	
}
//FRONTEN SIDE GET USER DASHBOARD REQUEST FUNCTION
function MJ_gmgt_user_dashboard()
{
	if(isset($_REQUEST['dashboard']))
	{
		require_once GMS_PLUGIN_DIR. '/fronted_template.php';
		exit;
	}
}
//GET USER CHOICE PAGE INSERT FUNCTION
function gmgt_user_choice_page() 
{
	if ( !get_option('gmgt_user_choice_page') ) 
	{
		$curr_page = array(
			'post_title' => __('Member Registration or Login', 'gym_mgt'),
			'post_content' => '[gmgt_memberregistration]',
			'post_status' => 'publish',
			'post_type' => 'page',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_category' => array(1),
			'post_parent' => 0 );
			$curr_created = wp_insert_post( $curr_page );
			update_option( 'gmgt_user_choice_page', $curr_created );
			
	}
}
// GET MEMBERSHP LIST PAGE INSERT FUNCTION WITH MEMBERSHP CODE //
function MJ_gmgt_membership_list_page()
{
	if ( !get_option('gmgt_membershiplist_page') )
	{
		$curr_page = array(
			'post_title' => __('Membership List Page', 'gym_mgt'),
			'post_content' => '[MembershipCode id=1]',
			'post_status' => 'publish',
			'post_type' => 'page',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_category' => array(1),
			'post_parent' => 0 );
			$curr_created = wp_insert_post( $curr_page );
			update_option( 'gmgt_membershiplist_page', $curr_created );
	}
}
//GET MEMBRSHIP LINK
function MJ_membershipcode_link($atts)
{
	if(isset($_POST['buy_membership']))
	{		
		$obj_member=new MJ_Gmgtmember;		
		$page_id = get_option ( 'gmgt_user_choice_page' );			
		$referrer_ipn = array(				
			'page_id' => $page_id,
			'membership_id'=>$_POST['membership_id']
		);				
		$referrer_ipn = add_query_arg( $referrer_ipn, home_url() );		
		wp_redirect ($referrer_ipn);	
		exit;
	}
		$obj_activity=new MJ_Gmgtactivity;
		$obj_membership=new MJ_Gmgtmembership;
		$atts = shortcode_atts( array(
		'id' => $atts['id'],
		'buttontxt' => __('Buy Now','gym_mgt')
		), $atts, 'gmgt_user_choice_page' );
		 $retrieved_data=$obj_membership->MJ_gmgt_get_single_membership($atts['id']);
		if(!empty($retrieved_data))
		{ 
			$result = MJ_gmgt_get_membership_class($retrieved_data->membership_id);
			if(!empty($result))
			{
					$fake="";
					if($result->classis_limit=='limited')
					{ 
						$fake=1;						
					}						
			}?>
		    <div class="wpgym-detail-box col-md-12">
				<div class="wpgym-border-box">
				<form name="membership" method="post" action="">
					<div class="wpgym-box-title">
						<span class="wpgym-membershiptitle">
							<?php echo $retrieved_data->membership_label;?>
						</span>
					</div>
					<div class="wpgym-course-lession-list">
					<?php echo $retrieved_data->membership_description;?>
					</div>
					<table>
					<thead>
					<tr>
						<th><?php _e('Installment Plan','gym_mgt');?></th>
						<th><?php _e('Cost','gym_mgt');?></th>
						<th> <?php if($fake==1)
							_e('Class',' gym_mgt');?>
						</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td><?php echo get_the_title($retrieved_data->install_plan_id);?></td>
						<td><?php echo MJ_gmgt_get_currency_symbol(get_option( 'gmgt_currency_code' ))."".$retrieved_data->installment_amount;?></td>
						<td><?php if($fake==1){
							print $result->on_of_classis;
						}	?></td>
					</tr>
					</tbody>
					</table>
					
					<span class="wpgym-btn-buynow">
					<?php echo MJ_gmgt_get_currency_symbol(get_option( 'gmgt_currency_code' ))."".$retrieved_data->membership_amount;?>
					
					<input type="hidden" name="amount" value="<?php echo  $retrieved_data->membership_amount;?>">
					<input type="hidden" name="member_id" value="<?php echo get_current_user_id();?>">
					<input type="hidden" name="membership_id" value="<?php echo $retrieved_data->membership_id;?>">
					</span>					
					<input type="submit" name="buy_membership" value="<?php if(isset($atts['buttontxt'])) echo $atts['buttontxt'];?>">						
					</form>
				</div>	
		    </div>
			<?php 
		}
}
function MJ_gmgt_pay_membership_amount()
{
	//MEMBERSHI PPAYMENT PROCES FUNCTION
	if(isset($_POST['payer_status']) && $_POST['payer_status'] == 'VERIFIED' && (isset($_POST['payment_status'])) && $_POST['payment_status']=='Completed' && isset($_REQUEST['fullpay'] ) && $_REQUEST['fullpay']=='yes')
	{		
		if(!empty($_POST))
		{
			$obj_membership_payment=new MJ_Gmgt_membership_payment;
			$obj_membership=new MJ_Gmgtmembership;	
			$obj_member=new MJ_Gmgtmember;
			
			$trasaction_id  = $_POST["txn_id"];
			$custom_array = explode("_",$_POST['custom']);
			
			$joiningdate=date("Y-m-d");
			$membership=$obj_membership->MJ_gmgt_get_single_membership($custom_array[1]);
			
			
			$validity=$membership->membership_length_id;
			$user_id=$custom_array[0];
			$expiredate= date('Y-m-d', strtotime($joiningdate. ' + '.$validity.' days'));
			$membership_status = 'continue';
			$payment_data = array();
			$membershippayment=$obj_membership_payment->MJ_gmgt_checkMembershipBuyOrNot($custom_array[0],$joiningdate,$expiredate);
			
			if(!empty($membershippayment))
			{
				global $wpdb;
				$table_gmgt_membership_payment=$wpdb->prefix.'Gmgt_membership_payment';
				$payment_data['payment_status'] = 0;
				$whereid['mp_id']=$membershippayment->mp_id;
				$wpdb->update( $table_gmgt_membership_payment, $payment_data ,$whereid);
				$plan_id =$membershippayment->mp_id;
			}
			else
			{
				global $wpdb;
				//invoice number generate
				$table_income=$wpdb->prefix.'gmgt_income_expense';
				$result_invoice_no=$wpdb->get_results("SELECT * FROM $table_income");						
				
				if(empty($result_invoice_no))
				{							
					$invoice_no='00001';
				}
				else
				{							
					$result_no=$wpdb->get_row("SELECT invoice_no FROM $table_income where invoice_id=(SELECT max(invoice_id) FROM $table_income)");
					$last_invoice_number=$result_no->invoice_no;
					$invoice_number_length=strlen($last_invoice_number);
					
					if($invoice_number_length=='5')
					{
						$invoice_no = str_pad($last_invoice_number+1, 5, 0, STR_PAD_LEFT);
					}
					else	
					{
						$invoice_no='00001';
					}				
				}
						
				$payment_data['invoice_no']=$invoice_no;
				$payment_data['member_id'] = $custom_array[0];
				$payment_data['membership_id'] = $custom_array[1];
				$payment_data['membership_fees_amount'] = MJ_gmgt_get_membership_price($custom_array[1]);
				$payment_data['membership_signup_amount'] = MJ_gmgt_get_membership_signup_amount($custom_array[1]);
				$payment_data['tax_amount'] = MJ_gmgt_get_membership_tax_amount($custom_array[1]);
				$membership_amount=$payment_data['membership_fees_amount'] + $payment_data['membership_signup_amount']+$payment_data['tax_amount'];
				$payment_data['membership_amount'] = $membership_amount;
				$payment_data['start_date'] = $joiningdate;
				$payment_data['end_date'] = $expiredate;
				$payment_data['membership_status'] = $membership_status;
				$payment_data['payment_status'] = 0;
				$payment_data['created_date'] = date("Y-m-d");
				$payment_data['created_by'] = $user_id;
				$plan_id = $obj_member->MJ_gmgt_add_membership_payment_detail($payment_data);
				
				//save membership payment data into income table							
				$membership_name=MJ_gmgt_get_membership_name($custom_array[1]);
				$entry_array[]=array('entry'=>$membership_name,'amount'=>MJ_gmgt_get_membership_price($custom_array[1]));	
				$entry_array1[]=array('entry'=>__("Membership Signup Fee","gym_mgt"),'amount'=>MJ_gmgt_get_membership_signup_amount($custom_array[1]));	
				$entry_array_merge=array_merge($entry_array,$entry_array1);
				$incomedata['entry']=json_encode($entry_array_merge);	
				
				$incomedata['invoice_type']='income';
				$incomedata['invoice_label']=__("Fees Payment","gym_mgt");
				$incomedata['supplier_name']=$custom_array[0];
				$incomedata['invoice_date']=date('Y-m-d');
				$incomedata['receiver_id']=$custom_array[0];
				$incomedata['amount']=$membership_amount;
				$incomedata['total_amount']=$membership_amount;
				$incomedata['invoice_no']=$invoice_no;
				$incomedata['tax_id']=MJ_gmgt_get_membership_tax($custom_array[1]);
				$incomedata['paid_amount']=$_POST['mc_gross_1'];
				$incomedata['payment_status']='Fully Paid';
				$result_income=$wpdb->insert( $table_income,$incomedata); 
			}			
			$feedata['mp_id']=$plan_id;			
			$feedata['amount']=$_POST['mc_gross_1'];
			$feedata['payment_method']='paypal';		
			$feedata['trasaction_id']=$trasaction_id ;
			$feedata['created_by']=$custom_array[0];
			$result=$obj_membership_payment->MJ_gmgt_add_feespayment_history($feedata);
			$payment_data=$obj_membership_payment->MJ_gmgt_get_single_membership_payment($plan_id);
			if($result)
			{
				$u = new WP_User($user_id);
				$u->remove_role( 'subscriber' );
				$u->add_role( 'member' );				
				$gmgt_hash=delete_user_meta($user_id, 'gmgt_hash');
				update_user_meta( $user_id, 'membership_id', $custom_array[1] );					
				wp_redirect(home_url() .'/?action=success');	
			}			
		}
		
		$page_id = get_option ( 'gmgt_login_page' );
		$referrer_ipn = array(				
			'page_id' => $page_id
		);
		$referrer_ipn = add_query_arg( $referrer_ipn, home_url() );
		wp_redirect ($referrer_ipn);	
		exit;
	}
}
function MJ_gmgt_membership_pay_link()
{
	require_once GMS_PLUGIN_DIR. '/template/membership_details.php';
}
//INSTAL MEMBERSHIP PAY PAGE
function MJ_gmgt_install_membership_pay_page()
{
	if ( !get_option('gmgt_membership_pay_page') ) 
	{
		$curr_page = array(
				'post_title' => __('Membership Payment', 'gym_mgt'),
				'post_content' => '[membership_pay_shortcode]',
				'post_status' => 'publish',
				'post_type' => 'page',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_category' => array(1),
				'post_parent' => 0 );
		$curr_created = wp_insert_post( $curr_page );
		update_option( 'gmgt_membership_pay_page', $curr_created );
	}
}
add_action( 'plugins_loaded', 'MJ_gmgt_domain_load' );
add_action('wp_enqueue_scripts','MJ_gmgt_load_script1');
add_action('init','MJ_gmgt_install_login_page');
add_action('init','MJ_gmgt_membership_list_page');
add_shortcode( 'gmgt_login','MJ_gmgt_login_link');
add_action('init','gmgt_user_choice_page');
add_shortcode( 'MembershipCode','MJ_membershipcode_link' );
add_shortcode( 'membership_pay_shortcode','MJ_gmgt_membership_pay_link' );
add_action('init','MJ_gmgt_install_membership_pay_page');
add_action('wp_head','MJ_gmgt_user_dashboard');
add_action( 'init', 'MJ_gmgt_pay_membership_amount');
add_action( 'init', 'MJ_gmgt_pay_membership_amount_frontend_side');
add_shortcode( 'gmgt_memberregistration', 'MJ_gmgt_member_choice' );
add_shortcode( 'gmgt_member_registration', 'MJ_gmgt_memberregistration_link' );
add_action('init','MJ_gmgt_output_ob_start');

//MEMBER CHOICE FUNCTION FOR LOGIN OR EXTING USER
function MJ_gmgt_member_choice($attr)
{
	 ?>
	<style>
	.user-choice-area {
	  float: left;
	  width: 100%;
	}
	.user-choice-block {
	  float: left;
	  width: 30%;
	}
	</style>	 
	<script type="text/javascript"	src="<?php echo GMS_PLUGIN_URL.'/assets/js/jquery-1.11.1.min.js'; ?>"></script>	
	<script type="text/javascript">
	jQuery(document).ready(function() 
	{
			jQuery('.student_login_form').show();
			jQuery('.student_registraion_form').hide();
			jQuery('.user_login_choice').change(function() {
				var choice="";
				if(jQuery('.user_login_choice').is(':checked')) { 
					 choice=jQuery(this).val();
					if(choice=='new_user'){
							jQuery('.student_registraion_form').show();
							jQuery('.student_login_form').hide();
						}
						else
						{
							jQuery('.student_login_form').show();
							jQuery('.student_registraion_form').hide();
						}
					}
				});
	});
			
	</script>	 
	<?php
	if (is_user_logged_in ()) {
		$page_id = get_option ( 'gmgt_membership_pay_page' );
		$referrer_ipn = array(				
			'page_id' => $page_id,
			'membership_id'=>$_REQUEST['membership_id']
		);
		$referrer_ipn = add_query_arg( $referrer_ipn, home_url() );
		wp_redirect ($referrer_ipn);	
			exit;
	}
	else { ?>
		<div class="user-choice-area">
			<div class="user-choice-block">
				<input class="user_login_choice" checked="true" type="radio" value="existing_user"  name="user_choice"><?php _e('Existing User','gym_mgt');?>
			</div>
			<div class="user-choice-block">					
				<input class="user_login_choice" type="radio" value="new_user"  name="user_choice"><?php _e('New User','gym_mgt');?>
			</div>
		</div>
			
		<div class="student_login_form"><?php echo do_shortcode('[gmgt_login]'); ?></div>	
		<div class="student_registraion_form"><?php echo do_shortcode('[gmgt_member_registration]'); ?></div>		
		<?php }
	
}
//MEMBER RAGISTATION LINK FUNCTION
function MJ_gmgt_memberregistration_link()
{
	ob_start();
    MJ_gmgt_member_registration_function();
    return ob_get_clean();	
}
//MEMBER RAGIDSTAION FORM FUNCTION IN FRONTEND SIDE
function MJ_gmgt_registration_form( $class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$Height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$member_convert,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$staff_id) 
{		
		wp_enqueue_script('gmgt-defaultscript', plugins_url( '/assets/js/jquery-1.11.1.min.js', __FILE__ ), array( 'jquery' ), '4.1.1', true );
		
		$lancode=get_locale();
		$code=substr($lancode,0,2);
		
	 	wp_enqueue_style( 'wcwm-validate-css', plugins_url( '/lib/validationEngine/css/validationEngine.jquery.css', __FILE__) );
	 	wp_register_script( 'jquery-1.8.2', plugins_url( '/lib/validationEngine/js/jquery-1.8.2.min.js', __FILE__), array( 'jquery' ) );
	 	wp_enqueue_script( 'jquery-1.8.2' );
	 	wp_register_script( 'jquery-validationEngine-'.$code.'', plugins_url( '/lib/validationEngine/js/languages/jquery.validationEngine-'.$code.'.js', __FILE__), array( 'jquery' ) );
	 	wp_enqueue_script( 'jquery-validationEngine-'.$code.'' );
	 	wp_register_script( 'jquery-validationEngine', plugins_url( '/lib/validationEngine/js/jquery.validationEngine.js', __FILE__), array( 'jquery' ) );
	 	wp_enqueue_script( 'jquery-validationEngine' );
		wp_enqueue_script('jquery-ui-datepicker');		
		wp_enqueue_script('gmgt-bootstrap-multiselect-js', plugins_url( '/assets/js/bootstrap-multiselect.js', __FILE__ ) );
		
		wp_enqueue_style( 'accordian-jquery-ui-css', plugins_url( '/assets/accordian/jquery-ui.css', __FILE__) );
		wp_enqueue_style( 'gmgt-bootstrap-multiselect-css', plugins_url( '/assets/css/bootstrap-multiselect.css', __FILE__) );
		wp_register_script('gmgt  -popup-front', plugins_url( 'assets/js/popup.js', __FILE__ ), array( 'jquery' ));
	   wp_enqueue_script('gmgt  -popup-front');
	   
	   wp_enqueue_script('gmgt-bootstrap-timepicker-js', plugins_url( '/assets/js/bootstrap-datepicker.js', __FILE__ ) );
	   wp_enqueue_style( 'gmgt -bootstrap-timepicker-css', plugins_url( '/assets/css/datepicker.min.css', __FILE__) );
	
	   wp_localize_script( 'gmgt  -popup-front', 'gmgt  ', array( 'ajax' => admin_url( 'admin-ajax.php' ) ) );
	   wp_enqueue_script('jquery');
	 ?>	
	<link rel="stylesheet"	href="<?php echo GMS_PLUGIN_URL.'/assets/css/fronted_user_registration.css'; ?>">	
	<link rel="stylesheet"	href="<?php echo GMS_PLUGIN_URL.'/assets/css/bootstrap-multiselect.css'; ?>">	
	<script type="text/javascript"	src="<?php echo GMS_PLUGIN_URL.'/assets/js/bootstrap.min.js'; ?>"></script>
	<script type="text/javascript"	src="<?php echo GMS_PLUGIN_URL.'/assets/js/bootstrap-multiselect.js'; ?>"></script>

   <script type="text/javascript">
    jQuery(document).ready(function()
	{
	  $('#registration_form').validationEngine({promptPosition : "bottomRight",maxErrorsPerField: 1});	
		$.fn.datepicker.defaults.format =" <?php echo get_option('gmgt_datepicker_format');?>";
		  $('#birth_date').datepicker({
		 endDate: '+0d',
			autoclose: true
			 
	   }); 	   
	   var date = new Date();
            date.setDate(date.getDate()-0);
	        $.fn.datepicker.defaults.format =" <?php echo get_option('gmgt_datepicker_format');?>";
             $('#inqiury_date').datepicker({
	         startDate: date,
             autoclose: true
           });
		var date = new Date();
            date.setDate(date.getDate()-0);
	        $.fn.datepicker.defaults.format =" <?php echo get_option('gmgt_datepicker_format');?>";
             $('#triel_date').datepicker({
	         startDate: date,
             autoclose: true
           });
		   var date = new Date();
            date.setDate(date.getDate()-0);
	        $.fn.datepicker.defaults.format =" <?php echo get_option('gmgt_datepicker_format');?>";
             $('#first_payment_date').datepicker({
	         startDate: date,
             autoclose: true
           });		   
		 $('#begin_date').datepicker({dateFormat: '<?php echo get_option('gmgt_datepicker_format');?>'});
		$('#group_id').multiselect({
			nonSelectedText :'<?php _e('Select Group','gym_mgt');?>',
			includeSelectAllOption: true
		 });
		$('#classis_id').multiselect({
			nonSelectedText :'<?php _e('Select Class','gym_mgt');?>',
			includeSelectAllOption: true
		 });		 
    } );
    </script>	
	<script type="text/javascript">
	function fileCheck(obj) 
	{
		var fileExtension = ['jpeg', 'jpg', 'png', 'gif', 'bmp',''];
		if ($.inArray($(obj).val().split('.').pop().toLowerCase(), fileExtension) == -1)
			alert("<?php _e("Only '.jpeg','.jpg', '.png', '.gif', '.bmp' formats are allowed.",'gym_mgt');?>");				
	}
	</script>
	<?php 
	$obj_class=new MJ_Gmgtclassschedule; 
	$obj_member=new MJ_Gmgtmember; 
	$obj_group=new MJ_Gmgtgroup;
	$obj_membership=new MJ_Gmgtmembership;
	$edit = 0; 
	$role="member";
	$lastmember_id=MJ_gmgt_get_lastmember_id($role);
	$nodate=substr($lastmember_id,0,-4);
	$memberno=substr($nodate,1);
	$memberno+=1;
	$newmember='M'.$memberno.date("my");
	?>		
	<div class="student_registraion_form"><!-- MEMBER REGISTRATION DIV START-->
		<form id="registration_form" action="<?php echo $_SERVER['REQUEST_URI'];?>" method="post" enctype="multipart/form-data"><!-- MEMBER REGISTRATION FORM START-->
			<input type="hidden" name="role" value=""  />
			<input type="hidden" name="user_id" value=""  />
			<div class="header">	
				<h3><?php _e('Personal Information','gym_mgt');?></h3>
			</div>
			<div class="form-group" style="margin-top:10px;">
				<label class="col-sm-2 control-label" for="member_id"><?php _e('Member Id','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="member_id" class="form-control validate[required]" type="text" 
					value="<?php if($edit){ echo $user_info->member_id;}else echo $newmember;?>"  readonly name="member_id">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="first_name"><?php _e('First Name','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="first_name" class="form-control validate[required,custom[onlyLetter_specialcharacter]] text-input" maxlength="50" type="text" value="" name="first_name">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="middle_name"><?php _e('Middle Name','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="middle_name" class="form-control validate[custom[onlyLetter_specialcharacter]]" maxlength="50" type="text"  value="" name="middle_name">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="last_name"><?php _e('Last Name','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="last_name" class="form-control validate[required,custom[onlyLetter_specialcharacter]] text-input" maxlength="50" type="text"  value="" name="last_name">
				</div>
			</div>
			
			<div class="form-group">
				<label class="col-sm-2 control-label" for="gender"><?php _e('Gender','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
				<?php $genderval = "male"; if($edit){ $genderval=$user_info->gender; }elseif(isset($_POST['gender'])) {$genderval=$_POST['gender'];}?>
					<label class="radio-inline">
					 <input type="radio" value="male" class="tog validate[required]" name="gender"  <?php  checked( 'male', $genderval);  ?>/><?php _e('Male','gym_mgt');?>
					</label>
					<label class="radio-inline">
					  <input type="radio" value="female" class="tog validate[required]" name="gender"  <?php  checked( 'female', $genderval);  ?>/><?php _e('Female','gym_mgt');?> 
					</label>
				</div>
			</div>
			
			<div class="form-group">
				<label class="col-sm-2 control-label" for="birth_date"><?php _e('Date of birth','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="birth_date" class="form-control validate[required]" type="text"  name="birth_date" data-date-format="<?php echo MJ_gmgt_bootstrap_datepicker_dateformat(get_option('gmgt_datepicker_format'));?>" value="" readonly>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="group_id"><?php _e('Group','gym_mgt');?></label>
				<div class="col-sm-8">
					<?php 					
					$groups_array=array();
					?>
					<?php if($edit){ $group_id=$user_info->group_id; }elseif(isset($_POST['group_id'])){$group_id=$_POST['group_id'];}else{$group_id='';}?>
					<select id="group_id"  name="group_id[]" multiple="multiple">				
					<?php $groupdata=$obj_group->MJ_gmgt_get_all_groups();
					 if(!empty($groupdata))
					 {
						foreach ($groupdata as $group){?>
							<option value="<?php echo $group->id;?>" <?php if(in_array($group->id,$groups_array)) echo 'selected';  ?>><?php echo $group->group_name; ?> </option>
				<?php } } ?>
				</select>				
				</div>
			</div>
			<div class="header">	
				<hr>
				<h3><?php _e('Contact Information','gym_mgt');?></h3>
			</div>
			<div class="form-group" style="margin-top:10px;">
				<label class="col-sm-2 control-label" for="address"><?php _e('Address','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="address" class="form-control validate[required,custom[address_description_validation]]" maxlength="150" type="text"  name="address" value="">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="city_name"><?php _e('City','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="city_name" class="form-control validate[required,custom[city_state_country_validation]]" maxlength="50" type="text"  name="city_name" value="">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="state_name"><?php _e('State','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="state_name" class="form-control validate[custom[city_state_country_validation]]" maxlength="50" type="text"  name="state_name" value="">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="zip_code"><?php _e('Zip Code','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="zip_code" class="form-control  validate[required,custom[onlyLetterNumber]]" maxlength="15" type="text"  name="zip_code" value="">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label " for="mobile"><?php _e('Mobile Number','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-1" style="padding-right:0px;">
				
				<input type="text" readonly value="+<?php echo MJ_gmgt_get_countery_phonecode(get_option( 'gmgt_contry' ));?>"  class="form-control" name="phonecode">
				</div>
				<div class="col-sm-7">
					<input id="mobile" class="form-control validate[required,custom[phone_number]] text-input" type="text"  name="mobile" minlength="6" maxlength="15"value="">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label " for="phone"><?php _e('Phone','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="phone" class="form-control validate[custom[phone_number]] text-input" type="text"  name="phone" minlength="6" maxlength="15" value="">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label " for="email"><?php _e('Email','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="email" class="form-control validate[required,custom[email]] text-input" maxlength="100" type="text"  name="email" value="">
				</div>
			</div>
			
			<div class="header">	<hr>
				<h3><?php _e('Physical Information','gym_mgt');?></h3>
			</div>
			<div class="form-group" style="margin-top:10px;">
				<label class="col-sm-2 control-label" for="weight"><?php _e('Weight','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="weight" class="form-control text-input" type="number" min="0"  onkeypress="if(this.value.length==6) return false;" step="0.01" value="" 	name="weight" placeholder="<?php echo get_option( 'gmgt_weight_unit' );?>">
					
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="height"><?php _e('Height','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="height" class="form-control text-input" type="number" min="0"  onkeypress="if(this.value.length==6) return false;" step="0.01" value="" 
					name="height" placeholder="<?php echo get_option( 'gmgt_height_unit' );?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="Chest"><?php _e('Chest','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="Chest" class="form-control text-input" type="number" min="0"  onkeypress="if(this.value.length==6) return false;" step="0.01" 
					value="" name="chest" 
					placeholder="<?php echo get_option( 'gmgt_chest_unit' );?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="Waist"><?php _e('Waist','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="waist" class="form-control text-input" type="number" min="0"  onkeypress="if(this.value.length==6) return false;" step="0.01" 
					value="" name="waist" 
					placeholder="<?php echo get_option( 'gmgt_waist_unit' );?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="thigh"><?php _e('Thigh','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="thigh" class="form-control text-input" type="number" min="0"  onkeypress="if(this.value.length==6) return false;" step="0.01" value="" name="thigh" 	placeholder="<?php echo get_option( 'gmgt_thigh_unit' );?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="arms"><?php _e('Arms','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="arms" class="form-control text-input" type="number" min="0"  onkeypress="if(this.value.length==6) return false;" step="0.01" value="" name="arms" placeholder="<?php echo get_option( 'gmgt_arms_unit' );?>">
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="fat"><?php _e('Fat','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="fat" class="form-control text-input" type="number" min="0" max="100"  onkeypress="if(this.value.length==6) return false;" step="0.01"
					value="">
				</div>
			</div>
			<div class="header">
				<hr>
				<h3><?php _e('Login Information','gym_mgt');?></h3>
			</div>
			<div class="form-group" style="margin-top:10px;">
				<label class="col-sm-2 control-label" for="username"><?php _e('User Name','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<input id="username" class="form-control validate[required,custom[username_validation]]" maxlength="50" type="text"  name="username" 
					value="<?php if($edit){ echo $user_info->user_login;}elseif(isset($_POST['username'])) echo $_POST['username'];?>" <?php if($edit) echo "readonly";?>>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="password"><?php _e('Password','gym_mgt');?><?php if(!$edit) {?><span class="require-field">*</span><?php }?></label>
				<div class="col-sm-8">
					<input id="password" class="form-control <?php if(!$edit) echo 'validate[required]';?>" type="password" minlength="8" maxlength="12"  name="password" value="">
				</div>
			</div>
			
			<div class="form-group">
				<label class="col-sm-2 control-label" for="photo"><?php _e('Image','gym_mgt');?></label>
				<div class="col-sm-8">
					<input type="file" onchange="fileCheck(this);"  class="form-control" name="gmgt_user_avatar"  >
				</div>	
				<div class="clearfix"></div>
			</div>
			<div class="header">	<hr>
				<h3><?php _e('More Information','gym_mgt');?></h3>
			</div>
			
			<div class="form-group" style="margin-top:10px;">
				<label class="col-sm-2 control-label" for="staff_name"><?php _e('Select Staff Member','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<?php $get_staff = array('role' => 'Staff_member');
						$staffdata=get_users($get_staff);
						
						?>
					<select name="staff_id" class="form-control validate[required] " id="staff_id">
					<option value=""><?php  _e('Select Staff Member','gym_mgt');?></option>
					<?php 
						if(!empty($staffdata))
						{
							foreach($staffdata as $staff)
							{						
								echo '<option value='.$staff->ID.' '.selected($staff_id,$staff->ID).'>'.$staff->display_name.'</option>';
							}
						}
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="intrest"><?php _e('Interest Area','gym_mgt');?></label>
				<div class="col-sm-8">
				
					<select class="form-control" name="intrest_area" id="intrest_area">
					<option value=""><?php _e('Select Interest','gym_mgt');?></option>
					<?php 
					
					if(isset($_REQUEST['intrest']))
						$category =$_REQUEST['intrest'];  
					elseif($edit)
						$category =$user_info->intrest_area;
					else 
						$category = "";
					
					$role_type=MJ_gmgt_get_all_category('intrest_area');
					if(!empty($role_type))
					{
						foreach ($role_type as $retrive_data)
						{
							echo '<option value="'.$retrive_data->ID.'" '.selected($category,$retrive_data->ID).'>'.$retrive_data->post_title.'</option>';
						}
					}
					?>					
					</select>
				</div>
				
			</div>
			<?php if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit'){?>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="member_convert"><?php  _e(' Convert into Staff Member','gym_mgt');?></label>
					<div class="col-sm-8">
					<input type="checkbox"  name="member_convert" value="staff_member">
					
					</div>
			</div>
			<?php }?>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="Source"><?php _e('Referral Source','gym_mgt');?></label>
				<div class="col-sm-8">				
					<select class="form-control" name="source" id="source">
					<option value=""><?php _e('Select Referral Source','gym_mgt');?></option>
					<?php 					
					if(isset($_REQUEST['source']))
						$category =$_REQUEST['source'];  
					elseif($edit)
						$category =$user_info->source;
					else 
						$category = "";
					
					$role_type=MJ_gmgt_get_all_category('source');
					if(!empty($role_type))
					{
						foreach ($role_type as $retrive_data)
						{
							echo '<option value="'.$retrive_data->ID.'" '.selected($category,$retrive_data->ID).'>'.$retrive_data->post_title.'</option>';
						}
					} ?>
					</select>
				</div>
				
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="refered"><?php _e('Referred By','gym_mgt');?></label>
				<div class="col-sm-8">
					<?php $get_staff = array('role' => 'Staff_member');
						$staffdata=get_users($get_staff);
						
						?>
					<select name="reference_id" class="form-control" id="reference_id">
					<option value=""><?php  _e('Select Referred Member','gym_mgt');?></option>
					<?php if($edit)
							$staff_data=$user_info->reference_id;
						elseif(isset($_POST['reference_id']))
							$staff_data=$_POST['reference_id'];
						else
							$staff_data="";					
						
						if(!empty($staffdata))
						{
							foreach($staffdata as $staff)
							{
								
								echo '<option value='.$staff->ID.' '.selected($staff_data,$staff->ID).'>'.$staff->display_name.'</option>';
							}
						}
						?>
					</select>
				</div>
				
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="inqiury_date"><?php _e('Inquiry Date','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="inqiury_date" class="form-control" type="text"  name="inqiury_date" data-date-format="<?php echo MJ_gmgt_bootstrap_datepicker_dateformat(get_option('gmgt_datepicker_format'));?>"
					value="" readonly>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="triel_date"><?php _e('Trial End Date','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="triel_date" class="form-control" type="text"  name="triel_date" data-date-format="<?php echo MJ_gmgt_bootstrap_datepicker_dateformat(get_option('gmgt_datepicker_format'));?>" 
					 value="" readonly>
				</div>
			</div>			
			<div class="form-group">
				<label class="col-sm-2 control-label" for="membership"><?php _e('Membership','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
				<input type="hidden" name="membership_hidden" class="membership_hidden" value="<?php  echo '0'; ?>">
					<?php 	
					$membershipdata=$obj_membership->MJ_gmgt_get_all_membership();?>
					
					<select name="membership_id" class="form-control validate[required] " id="membership_id">
					<?php				
					
					if(isset($_REQUEST['membership_id']))
					{
						$membership_id=$_REQUEST['membership_id'];
						 ?>
						<option value="<?php echo $membership_id; ?>"><?php echo MJ_gmgt_get_membership_name($membership_id);?></option>
						<?php 
					}
					else
					{
						?>
						<option value=""><?php  _e('Select Membership ','gym_mgt');?></option>
						<?php 
						if(!empty($membershipdata))
						{
							foreach ($membershipdata as $membership)
							{						
								echo '<option value='.$membership->membership_id.' '.selected($staff_data,$membership->membership_id).'>'.$membership->membership_label.'</option>';
							}
						}
					}
					?>
					</select>
				</div>
			</div>			
			<div class="form-group">
				<label class="col-sm-2 control-label" for="class_id"><?php _e('Class','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">					
				<select id="classis_id" class="form-control validate[required] classis_ids" multiple="multiple" name="class_id[]">
					<?php
					if(isset($_REQUEST['membership_id']))
					{
						global $wpdb;	
						$tbl_gmgt_membership_class = $wpdb->prefix."gmgt_membership_class";	
						$retrive_data = $wpdb->get_results("SELECT * FROM $tbl_gmgt_membership_class WHERE membership_id=".$_REQUEST['membership_id']);
						if(!empty($retrive_data))
						{
							foreach($retrive_data as $key=>$value)
							{
								?>
								<option value="<?php echo $value->class_id; ?>"><?php echo MJ_gmgt_get_class_name($value->class_id); ?></option>
								<?php
							}
						}						
					}
					?>					
				</select>
				</div>				
			</div>
			
			<div class="form-group">
				<label class="col-sm-2 control-label" for="begin_date"><?php _e('Membership Valid From','gym_mgt');?><span class="require-field">*</span></label>
				<div class="col-sm-8">
					<div class="col-sm-12">
					<input id="begin_date" class="form-control validate[required] begin_date" type="text" data-date-format="<?php echo MJ_gmgt_bootstrap_datepicker_dateformat(get_option('gmgt_datepicker_format'));?>"  name="begin_date" 
					value="" readonly>
					</div>					
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="begin_date"><?php _e('To','gym_mgt');?></label>
				<div class="col-sm-8">					
					<div class="col-sm-12">
					<input id="end_date" class="form-control validate[required]" type="text" data-date-format="<?php echo MJ_gmgt_bootstrap_datepicker_dateformat(get_option('gmgt_datepicker_format'));?>"   name="end_date" 
					value="" readonly>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="first_payment_date"><?php _e('First Payment Date','gym_mgt');?></label>
				<div class="col-sm-8">
					<input id="first_payment_date" class="form-control" type="text"  name="first_payment_date" data-date-format="<?php echo MJ_gmgt_bootstrap_datepicker_dateformat(get_option('gmgt_datepicker_format'));?>" 
					value="" readonly>
				</div>
			</div>
			
			<div class="col-sm-offset-2 col-sm-8"> 
				<input type="submit" value="<?php _e('Registration','gym_mgt');?>" name="save_member_front" class="btn btn-success"/>
			</div>
		</form><!-- MEMBER REGISTRATION FORM END-->
	</div><!-- MEMBER REGISTRATION DIV END-->
	<?php
}
//MEMBER RAGISTATION FUNCTION 
function MJ_gmgt_member_registration_function() 
{
	global $class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$alternet_mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$member_convert,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$group_id,$staff_id;
	$class_name = isset($_POST['class_id'])?$_POST['class_id']:'';
	  //SAVE FRONTED MEMBER DATA 
    if ( isset($_POST['save_member_front'] ) )
	{		
        MJ_gmgt_registration_validation(
		$_POST['class_id'],
		$_POST['first_name'],
		$_POST['last_name'],
		$_POST['gender'],
		$_POST['birth_date'],
		$_POST['address'],
		$_POST['city_name'],
		$_POST['state_name'],
		$_POST['zip_code'],
		$_POST['mobile'],
		$_POST['email'],
        $_POST['username'],
        $_POST['password'],        
		$_POST['membership_id'],
        $_POST['begin_date'],
        $_POST['end_date'], 
        $_POST['staff_id']);
         
		 
        // sanitize user form input//
        global $class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$alternet_mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$member_convert,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$group_id,$staff_id;
        if(isset($_POST['class_id'])){ $class_name =$_POST['class_id']; } else { echo $class_name =""; } 
		
		$first_name =    MJ_gmgt_strip_tags_and_stripslashes($_POST['first_name']) ;
		$middle_name =   MJ_gmgt_strip_tags_and_stripslashes($_POST['middle_name']) ;
		$last_name =  MJ_gmgt_strip_tags_and_stripslashes($_POST['last_name']);
		$gender =   $_POST['gender'] ;
		$birth_date =   $_POST['birth_date'] ;
		$address =   MJ_gmgt_strip_tags_and_stripslashes($_POST['address']);
		$city_name =    MJ_gmgt_strip_tags_and_stripslashes($_POST['city_name']);
		$state_name =   MJ_gmgt_strip_tags_and_stripslashes($_POST['state_name']);
		$zip_code =   MJ_gmgt_strip_tags_and_stripslashes($_POST['zip_code']);
		$mobile_number =   $_POST['mobile'] ;
		if(!empty($_POST['group_id']))
			$group_id =   $_POST['group_id'] ;
		else
			$group_id=array();
		
		$phone =   $_POST['phone'] ;		
		$username   =    MJ_gmgt_strip_tags_and_stripslashes($_POST['username']);
        $password   =    MJ_gmgt_password_validation($_POST['password']);
        $email      =    MJ_gmgt_strip_tags_and_stripslashes($_POST['email']);
        $gmgt_user_avatar      = $_FILES['gmgt_user_avatar'] ;
        $member_id      =    $_POST['member_id'] ;
        $weight      =    $_POST['weight'] ;
        $height      =    $_POST['height'] ;
        $chest      =    $_POST['chest'] ;
        $waist      =    $_POST['waist'] ;
        $thigh      =    $_POST['thigh'] ;
        $arms      =    $_POST['arms'] ;
        $fat      =    $_POST['fat'] ;
        $intrest_area      =    $_POST['intrest_area'] ;
		
        
        $source      =    $_POST['source'] ;
        $reference_id      =    $_POST['reference_id'] ;
        $inqiury_date      =    $_POST['inqiury_date'] ;
        $membership_id      =    $_POST['membership_id'] ;
        $begin_date      =    $_POST['begin_date'] ;
        $end_date      =    $_POST['end_date'] ;
        $staff_id      =    $_POST['staff_id'] ;
        $first_payment_date      =    $_POST['first_payment_date'] ;
       
        // call @function complete_registration to create the user
        // only when no WP_error is found
        MJ_gmgt_complete_registration(
        $class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$alternet_mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$group_id,$staff_id
        );
	 }
	MJ_gmgt_registration_form(
       $class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$alternet_mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$group_id,$staff_id);

}
//REGISTRATION Completed FUNCTION
function MJ_gmgt_complete_registration($class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$alternet_mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$group_id,$staff_id)
{
   $obj_member=new MJ_Gmgtmember;    
   global $reg_errors;
   global $wpdb;
	 global $class_name,$first_name,$middle_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$alternet_mobile_number,$phone,$email,$username,$password,$gmgt_user_avatar,$member_id,$weight,$height,$chest,$waist,$thigh,$arms,$fat,$intrest_area,$source,$reference_id,$inqiury_date,$membership_id,$begin_date,$end_date,$first_payment_date,$group_id,$staff_id;
	 $smgt_avatar = '';	
		
    if ( 1 > count( $reg_errors->get_error_messages() ) ) 
	{
        $userdata = array(
        'user_login'    =>   $username,
        'user_email'    =>   $email,
        'user_pass'     =>   $password,
        'user_url'      =>   NULL,
        'first_name'    =>   $first_name,
        'last_name'     =>   $last_name,
        'nickname'      =>   NULL
        );
        
		$user_id = wp_insert_user( $userdata );
	
 		$user = new WP_User($user_id);
		$user->set_role('member');
		$smgt_avatar = '';
		$table_gmgt_groupmember = $wpdb->prefix.'gmgt_groupmember';
		if($_FILES['gmgt_user_avatar']['size'] > 0)
		{
			$gmgt_avatar_image = MJ_gmgt_user_avatar_image_upload('gmgt_user_avatar');
			$gmgt_avatar = content_url().'/uploads/gym_assets/'.$gmgt_avatar_image;
		}
		else
		{
			$gmgt_avatar = '';
		}
		$usermetadata=array(					
			'middle_name'=>$middle_name,
			'gender'=>$gender,
			'birth_date'=>$birth_date,
			'address'=>$address,
			'city_name'=>$city_name,
			'state_name'=>$state_name,
			'zip_code'=>$zip_code,			
			'phone'=>$phone,
			'mobile'=>$mobile_number,
			'gmgt_user_avatar'=>$gmgt_avatar,
			'member_id'=>$member_id,
			'member_type'=>'Member',
			'height'=>$height,
			'weight'=>$weight,
			'chest'=>$chest,
			'waist'=>$waist,
			'thigh'=>$thigh,
			'arms'=>$arms,
			'fat'=>$fat,
			'staff_id'=>$staff_id,
			'intrest_area'=>$intrest_area,
			'source'=>$source,
			'reference_id'=>$reference_id,
			'inqiury_date'=>$inqiury_date,
			'membership_id'=>$membership_id,
			'begin_date'=>$begin_date,
			'end_date'=>$end_date,
			'first_payment_date'=>$first_payment_date);
		
		foreach($usermetadata as $key=>$val)
		{		
			update_user_meta( $user_id, $key,$val );	
		}	
		
		global $wpdb;
		$table_gmgt_member_class = $wpdb->prefix. 'gmgt_member_class';
		$memclss['member_id']=$user_id;
		foreach($class_name as $key=>$class)
		{
			$memclss['class_id']=$class;
			$result = $wpdb->insert($table_gmgt_member_class,$memclss);			
		} 
		
		if(!empty($group_id))
		{
			if($obj_member->MJ_gmgt_member_exist_ingrouptable($user_id))
				$obj_member->MJ_gmgt_delete_member_from_grouptable($user_id);
			foreach($group_id as $id)
			{
				$group_data['group_id']=$id;
				$group_data['member_id']=$user_id;
				$group_data['created_date']=date("Y-m-d");
				$group_data['created_by']=$user_id;
				$wpdb->insert( $table_gmgt_groupmember, $group_data );
			}
		}
							  
		  $hash = md5( rand(0,1000) );
		  update_user_meta( $user_id, 'gmgt_hash', $hash );
		  $user_info = get_userdata($user_id);

			$gymname=get_option( 'gmgt_system_name' );
			$to = $user_info->user_email;         
			$subject = get_option('registration_title'); 
			$sub_arr['[GMGT_GYM_NAME]']=$gymname;
			$subject = MJ_gmgt_subject_string_replacemnet($sub_arr,$subject);
			$search=array('[GMGT_MEMBERNAME]','[GMGT_MEMBERID]','[GMGT_STARTDATE]','[GMGT_ENDDATE]','[GMGT_MEMBERSHIP]','[GMGT_GYM_NAME]');
			$membership_name=MJ_gmgt_get_membership_name($membership_id);
			$replace = array($user_info->display_name,$user_info->member_id,$begin_date,$end_date,$membership_name,get_option( 'gmgt_system_name' ));
			$message_replacement = str_replace($search, $replace,get_option('registration_mailtemplate'));
	
			MJ_gmgt_send_mail($to,$subject,$message_replacement);			
										
        echo 'Registration complete.Your account active after admin can approve.'; 
		if($user_id)
		{
				$page_id = get_option ( 'gmgt_membership_pay_page' );
				$referrer_ipn = array(				
					'page_id' => $page_id,
					'user_id' => $user_id,
					'membership_id'=>$membership_id);
				$referrer_ipn = add_query_arg( $referrer_ipn, home_url() );
				wp_redirect ($referrer_ipn);	
			exit;	
		}			
	}	
}

//MEMBER RAGISTATION FORM VALIDATION FUNCTION//
function MJ_gmgt_registration_validation($class_name,$first_name,$last_name,$gender,$birth_date,$address,$city_name,$state_name,$zip_code,$mobile_number,$email,$username,$password,$membership_id,$begin_date,$end_date,$staff_id)  
{
	global $reg_errors;
	$reg_errors = new WP_Error;
	if ( empty( $class_name )  || empty( $first_name ) || empty( $last_name ) || empty( $birth_date ) || empty( $address ) || empty( $city_name ) || empty( $zip_code ) ||  empty( $email ) || empty( $username ) || empty( $password ) || empty( $membership_id ) || empty( $begin_date )|| empty( $end_date ) || empty( $staff_id )) 
	{
    $reg_errors->add('field', 'Required form field is missing');
	}
	if ( 4 > strlen( $username ) ) {
    $reg_errors->add( 'username_length', 'Username too short. At least 4 characters is required' );
	}
	if ( username_exists( $username ) )
		$reg_errors->add('user_name', 'Sorry, that username already exists!');
	if ( ! validate_username( $username ) ) {
    $reg_errors->add( 'username_invalid', 'Sorry, the username you entered is not valid' );
	}
	
	if ( !is_email( $email ) ) {
    $reg_errors->add( 'email_invalid', 'Email is not valid' );
	}
	if ( email_exists( $email ) ) {
    $reg_errors->add( 'email', 'Email Already in use' );
	}
	
	if ( is_wp_error( $reg_errors ) ) 
	{ 
		foreach ( $reg_errors->get_error_messages() as $error )
		{     
			echo '<div class="student_reg_error">';
			echo '<strong>ERROR</strong> : ';
			echo '<span class="error"> '.$error . ' </span><br/>';
			echo '</div>';         
		} 
	}	
}
//OUTPUT OB START FUNCTION
function MJ_gmgt_output_ob_start()
{
	ob_start();
}
///INSTALL TABLE PLUGIN ACTIVATE DEAVTIVATE TIME
function MJ_gmgt_install_tables()
{
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	global $wpdb;
	
	$table_gmgt_activity = $wpdb->prefix . 'gmgt_activity';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_activity ." (
				  `activity_id` int(11) NOT NULL AUTO_INCREMENT,
				  `activity_cat_id` int(11) NOT NULL,
				  `activity_title` varchar(200) NOT NULL,
				  `activity_assigned_to` int(11) NOT NULL,
				  `activity_added_by` int(11) NOT NULL,
				  `activity_added_date` date NOT NULL,
				  PRIMARY KEY (`activity_id`)
				) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_assign_workout = $wpdb->prefix . 'gmgt_assign_workout';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_assign_workout." (
				  `workout_id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `user_id` bigint(20) NOT NULL,
				  `start_date` date NOT NULL,
				  `end_date` date NOT NULL,
				  `level_id` int(11) NOT NULL,
				  `description` text NOT NULL,
				  `created_date` datetime NOT NULL,
				  `created_by` bigint(20) NOT NULL,
				  PRIMARY KEY (`workout_id`)
				) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_attendence = $wpdb->prefix . 'gmgt_attendence';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_attendence." (
				 `attendence_id` int(11) NOT NULL AUTO_INCREMENT,
				  `user_id` int(11) NOT NULL,
				  `class_id` int(11) NOT NULL,
				  `attendence_date` date NOT NULL,
				  `status` varchar(50) NOT NULL,
				  `attendence_by` int(11) NOT NULL,
				  `role_name` varchar(50) NOT NULL,
				  PRIMARY KEY (`attendence_id`)
				) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		
		$table_gmgt_class_schedule = $wpdb->prefix . 'gmgt_class_schedule';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_class_schedule." (
				 `class_id` int(11) NOT NULL AUTO_INCREMENT,
				  `class_name` varchar(100) NOT NULL,
				  `day` text NOT NULL,
				  `staff_id` int(11) NOT NULL,
				  `asst_staff_id` int(11) NOT NULL,
				  `start_time` varchar(20) NOT NULL,
				  `end_time` varchar(20) NOT NULL,
				  `class_created_id` int(11) NOT NULL,
				  `class_creat_date` date NOT NULL,
				  PRIMARY KEY (`class_id`)
				) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		
		$table_gmgt_daily_workouts = $wpdb->prefix . 'gmgt_daily_workouts';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_daily_workouts." (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `workout_id` int(11) NOT NULL,
				  `member_id` int(11) NOT NULL,
				  `record_date` date NOT NULL,
				  `result_measurment` varchar(50) NOT NULL,
				  `result` varchar(100) NOT NULL,
				  `duration` varchar(100) NOT NULL,
				  `assigned_by` int(11) NOT NULL,
				  `due_date` date NOT NULL,
				  `time_of_workout` varchar(50) NOT NULL,
				  `status` varchar(100) NOT NULL,
				  `note` text NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `created_date` date NOT NULL,
				  PRIMARY KEY (`id`)
				)  DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		
		$table_gmgt_groups = $wpdb->prefix . 'gmgt_groups';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_groups." (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `group_name` varchar(100) NOT NULL,
				  `gmgt_groupimage` varchar(255) NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `created_date` date NOT NULL,
				  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_groupmember = $wpdb->prefix . 'gmgt_groupmember';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_groupmember." (
				  `id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `group_id` int(11) NOT NULL,
				  `member_id` int(11) NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `created_date` datetime NOT NULL,
				  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_income_expense = $wpdb->prefix . 'gmgt_income_expense';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_income_expense." (
				  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
				  `invoice_type` varchar(100) NOT NULL,
				  `invoice_label` varchar(100) NOT NULL,
				  `supplier_name` varchar(100) NOT NULL,
				  `entry` text NOT NULL,
				  `payment_status` varchar(50) NOT NULL,
				  `receiver_id` int(11) NOT NULL,
				  `invoice_date` date NOT NULL,
				  `invoice_no` varchar(100) NOT NULL,
				  `discount` double NOT NULL,
				  `total_amount` double NOT NULL,
				  `paid_amount` double NOT NULL,
				  `tax` double NOT NULL,
				  `due_amount` double NOT NULL,
				  `create_by` int(11) NOT NULL,
				  PRIMARY KEY (`invoice_id`)
				)  DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		
		$table_gmgt_membershiptype= $wpdb->prefix . 'gmgt_membershiptype';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_membershiptype." (
				  `membership_id` int(11) NOT NULL AUTO_INCREMENT,
				  `membership_label` varchar(100) NOT NULL,
				  `membership_cat_id` int(11) NOT NULL,
				  `membership_length_id` int(11) NOT NULL,
				  `membership_class_limit` varchar(20) NOT NULL,
				  `install_plan_id` int(11) NOT NULL,
				  `membership_amount` double NOT NULL,
				  `installment_amount` double NOT NULL,
				  `signup_fee` double NOT NULL,
				  `gmgt_membershipimage` varchar(255) NOT NULL,
				  `created_date` date NOT NULL,
				  `created_by_id` int(11) NOT NULL,
				  PRIMARY KEY (`membership_id`)
				)  DEFAULT CHARSET=utf8";
		$wpdb->query($sql);
		
		$table_gmgt_nutrition = $wpdb->prefix . 'gmgt_nutrition';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_nutrition." (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `user_id` int(11) NOT NULL,
				  `day` varchar(50) NOT NULL,
				  `breakfast` text NOT NULL,
				  `midmorning_snack` text NOT NULL,
				  `lunch` text NOT NULL,
				  `afternoon_snack` text NOT NULL,
				  `dinner` text NOT NULL,
				  `afterdinner_snack` text NOT NULL,
				  `start_date` varchar(20) NOT NULL,
				  `expire_date` varchar(20) NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `created_date` date NOT NULL,
				  PRIMARY KEY (`id`)
				)DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_payment = $wpdb->prefix . 'gmgt_payment';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_payment." (
				 `payment_id` int(11) NOT NULL AUTO_INCREMENT,
				  `title` varchar(100) NOT NULL,
				  `member_id` int(11) NOT NULL,
				  `due_date` date NOT NULL,
				  `unit_price` double NOT NULL,
				  `discount` double NOT NULL,
				  `total_amount` double NOT NULL,
				  `amount` double NOT NULL,
				  `payment_status` varchar(50) NOT NULL,
				  `payment_date` date NOT NULL,
				  `receiver_id` int(11) NOT NULL,
				  `description` text NOT NULL,
				  PRIMARY KEY (`payment_id`)
				)DEFAULT CHARSET=utf8";
					
		$wpdb->query($sql);
		
		
		$table_gmgt_product = $wpdb->prefix . 'gmgt_product';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_product." (
				 `id` int(11) NOT NULL AUTO_INCREMENT,
				  `product_name` varchar(100) NOT NULL,
				  `price` double NOT NULL,
				  `quentity` int(11) NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `created_date` date NOT NULL,
				  PRIMARY KEY (`id`)
				)DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		
		$table_gmgt_reservation = $wpdb->prefix . 'gmgt_reservation';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_reservation." (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `event_name` varchar(100) NOT NULL,
				  `event_date` date NOT NULL,
				  `start_time` varchar(20) NOT NULL,
				  `end_time` varchar(20) NOT NULL,
				  `place_id` int(11) NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `created_date` date NOT NULL,
				  PRIMARY KEY (`id`)
				)DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);		
	 
		$table_gmgt_store = $wpdb->prefix . 'gmgt_store';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_store."(
				  `id` int(11) NOT NULL AUTO_INCREMENT,				 
				  `invoice_no` varchar(50) NOT NULL,	
					`member_id` int(11) NOT NULL,				  
				  `entry` text NOT NULL,		  				  
				  `tax` double NOT NULL,
				  `discount` double NOT NULL,
				  `amount` double NOT NULL,
				  `total_amount` double NOT NULL,
				  `paid_amount` double NOT NULL,
				  `payment_status` varchar(50) NOT NULL,
				  `sell_by` int(11) NOT NULL,
				  `sell_date` date NOT NULL,
				  `created_date` date NOT NULL,
				  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8";
					
		$wpdb->query($sql);
		
		$table_gmgt_message= $wpdb->prefix . 'Gmgt_message';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_message." (
			  `message_id` int(11) NOT NULL AUTO_INCREMENT,
			  `sender` int(11) NOT NULL,
			  `receiver` int(11) NOT NULL,
			  `date` datetime NOT NULL,
			  `subject` varchar(150) NOT NULL,
			  `message_body` text NOT NULL,
			  `status` int(11) NOT NULL,
			  PRIMARY KEY (`message_id`)
			)DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_workout_data= $wpdb->prefix . 'gmgt_workout_data';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_workout_data." (
			  `id` bigint(20) NOT NULL AUTO_INCREMENT,
			  `day_name` varchar(15) NOT NULL,
			  `workout_name` varchar(100) NOT NULL,
			  `sets` int(11) NOT NULL,
			  `reps` int(11) NOT NULL,
			  `kg` float NOT NULL,
			  `time` int(11) NOT NULL,
			  `workout_id` bigint(20) NOT NULL,
			  `created_date` datetime NOT NULL,
			  `create_by` bigint(20) NOT NULL,
			  PRIMARY KEY (`id`)
			)DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);
		
		$table_gmgt_measurment= $wpdb->prefix . 'gmgt_measurment';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_measurment." (
			  `measurment_id` int(11) NOT NULL AUTO_INCREMENT,
			  `result_measurment` varchar(100) NOT NULL,
			  `result` int(11) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `result_date` date NOT NULL,
			  `created_by` int(11) NOT NULL,
			  `created_date` date NOT NULL,
			  PRIMARY KEY (`measurment_id`)
			)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_user_workouts= $wpdb->prefix . 'gmgt_user_workouts';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_user_workouts." (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `user_workout_id` int(11) NOT NULL,
			  `workout_name` varchar(200) NOT NULL,
			  `sets` int(11) NOT NULL,
			  `reps` int(11) NOT NULL,
			  `kg` float NOT NULL,
			  `rest_time` int(11) NOT NULL,
			  PRIMARY KEY (`id`)
			)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_nutrition_data= $wpdb->prefix . 'gmgt_nutrition_data';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_nutrition_data." (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `day_name` varchar(30) NOT NULL,
				  `nutrition_time` varchar(30) NOT NULL,
				  `nutrition_value` text NOT NULL,
				  `nutrition_id` int(11) NOT NULL,
				  `created_date` date NOT NULL,
				  `create_by` int(11) NOT NULL,
				  PRIMARY KEY (`id`)
				)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_membership_payment= $wpdb->prefix . 'Gmgt_membership_payment';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_membership_payment." (
				  `mp_id` int(11) NOT NULL AUTO_INCREMENT,
				  `member_id` int(11) NOT NULL,
				  `membership_id` int(11) NOT NULL,
				  `invoice_no` 	varchar(10) NOT NULL,
				  `membership_amount` double NOT NULL,
				  `paid_amount` double NOT NULL,
				  `start_date` date NOT NULL,
				  `end_date` date NOT NULL,
				  `membership_status` varchar(50) NOT NULL,
				  `payment_status` varchar(20) NOT NULL,
				  `created_date` date NOT NULL,
				  `created_by` int(11) NOT NULL,
				  PRIMARY KEY (`mp_id`)
				)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_membership_payment_history = $wpdb->prefix . 'gmgt_membership_payment_history';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_membership_payment_history." (
				  `payment_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `mp_id` int(11) NOT NULL,
				  `amount` int(11) NOT NULL,
				  `payment_method` varchar(50) NOT NULL,
				  `paid_by_date` date NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `trasaction_id` varchar(255) NOT NULL,
				  PRIMARY KEY (`payment_history_id`)
				)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_alert_mail_log = $wpdb->prefix . 'gmgt_alert_mail_log';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_alert_mail_log." (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `member_id` int(11) NOT NULL,
				  `membership_id` int(11) NOT NULL,
				  `start_date` varchar(20) NOT NULL,
				  `end_date` varchar(20) NOT NULL,
				  `alert_date` int(11) NOT NULL,
				  PRIMARY KEY (`id`)
				)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_message_replies = $wpdb->prefix . 'gmgt_message_replies';
		$sql = "CREATE TABLE ".$table_gmgt_message_replies." (
			  `id` int(20) NOT NULL AUTO_INCREMENT,
			  `message_id` int(20) NOT NULL,
			  `sender_id` int(20) NOT NULL,
			  `receiver_id` int(20) NOT NULL,
			  `message_comment` text NOT NULL,
			  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8";
	
		$wpdb->query($sql);	
		
		
		$table_gmgt_membership_activities = $wpdb->prefix . 'gmgt_membership_activities';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_membership_activities." (
		  `id` bigint(11) NOT NULL AUTO_INCREMENT,
		  `activity_id` int(11) NOT NULL,
		  `membership_id` int(11) NOT NULL,
		  `created_by` int(11) NOT NULL,
		  `created_date` date NOT NULL,
		  PRIMARY KEY (`id`)
		)DEFAULT CHARSET=utf8";		
		$wpdb->query($sql);
		
		
		$table_gmgt_member_class = $wpdb->prefix . 'gmgt_member_class';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_member_class." (
		  `id` int(20) NOT NULL AUTO_INCREMENT,
		  `member_id` int(20) NOT NULL,
		  `class_id` int(20) NOT NULL,
		   PRIMARY KEY (`id`)
		)DEFAULT CHARSET=utf8";		
		$wpdb->query($sql);
		
		$teacher_class = $wpdb->get_results("SELECT *from $table_gmgt_member_class");	
		if(empty($teacher_class))
		{
			$memberlist = get_users(array('role'=>'member'));
		
			if(!empty($memberlist))
			{
				foreach($memberlist as $retrieve_data)
				{				
					$created_by = get_current_user_id();
					$created_date = date('Y-m-d H:i:s');
					$class_id = get_user_meta($retrieve_data->ID,'class_id',true);				
					$success = $wpdb->insert($table_gmgt_member_class,array('member_id'=>$retrieve_data->ID,
						'class_id'=>$class_id,
						));
				}
			}		
		}
	
	$table_gmgt_booking_class = $wpdb->prefix . 'gmgt_booking_class';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_booking_class." (
		  `id` int(20) NOT NULL AUTO_INCREMENT,
		  `member_id` int(20) NOT NULL,
		  `class_id` int(20) NOT NULL,		
		   `booking_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `membership_id` int(10) NOT NULL,
		  `booking_day` varchar(255) NOT NULL,
		  `class_booking_date` date NOT NULL,
		  PRIMARY KEY (`id`)
		)DEFAULT CHARSET=utf8";		
		$wpdb->query($sql);
		
		$table_gmgt_membership_class = $wpdb->prefix . 'gmgt_membership_class';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_membership_class." (
		 `id` int(20) NOT NULL AUTO_INCREMENT,
		  `class_id` int(20) NOT NULL,
		  `membership_id` int(20) NOT NULL,
		  `booking_day` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`)
		)DEFAULT CHARSET=utf8";		
		$wpdb->query($sql);
		
		
		$table_gmgt_sales_payment_history = $wpdb->prefix . 'gmgt_sales_payment_history';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_sales_payment_history." (
				  `payment_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `sell_id` int(11) NOT NULL,
				  `member_id` int(11) NOT NULL,
				  `amount` int(11) NOT NULL,
				  `payment_method` varchar(50) NOT NULL,
				  `paid_by_date` date NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `trasaction_id` varchar(255) NOT NULL,
				  PRIMARY KEY (`payment_history_id`)
				)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);
		
		$table_gmgt_income_payment_history = $wpdb->prefix . 'gmgt_income_payment_history';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_income_payment_history." (
				  `payment_history_id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `invoice_id` int(11) NOT NULL,
				  `member_id` int(11) NOT NULL,
				  `amount` int(11) NOT NULL,
				  `payment_method` varchar(50) NOT NULL,
				  `paid_by_date` date NOT NULL,
				  `created_by` int(11) NOT NULL,
				  `trasaction_id` varchar(255) NOT NULL,
				  PRIMARY KEY (`payment_history_id`)
				)DEFAULT CHARSET=utf8";
				
		$wpdb->query($sql);		
		
		$table_gmgt_taxes = $wpdb->prefix . 'gmgt_taxes';
		$sql = "CREATE TABLE IF NOT EXISTS ".$table_gmgt_taxes." (
				  `tax_id` int(11) NOT NULL AUTO_INCREMENT,
				  `tax_title` varchar(255) NOT NULL,
				  `tax_value` double NOT NULL,
				   `created_date` date NOT NULL,	 
				  PRIMARY KEY (`tax_id`)
				) DEFAULT CHARSET=utf8";
		$wpdb->query($sql);		
		
		$table_gmgt_measurment= $wpdb->prefix . 'gmgt_measurment';
	$results='result';
	$result= $wpdb->query("ALTER TABLE $table_gmgt_measurment MODIFY COLUMN $results FLOAT");
	
	$table_gmgt_membership_payment_history = $wpdb->prefix . 'gmgt_membership_payment_history';
	$trasaction_id='trasaction_id';
	$result= $wpdb->query("ALTER TABLE $table_gmgt_membership_payment_history MODIFY COLUMN $trasaction_id varchar(255)");
	
	$table_gmgt_membershiptype= $wpdb->prefix . 'gmgt_membershiptype';
	$comment_field='membership_description';
	
	if (!in_array($comment_field, $wpdb->get_col( "DESC " . $table_gmgt_membershiptype, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $table_gmgt_membershiptype  ADD   $comment_field  text");
	}
	
	$table_gmgt_measurment= $wpdb->prefix . 'gmgt_measurment';
	$progress_image='gmgt_progress_image';
	if (!in_array($progress_image, $wpdb->get_col( "DESC " . $table_gmgt_measurment, 0 ) ))
	{  
		$result= $wpdb->query("ALTER     TABLE $table_gmgt_measurment  ADD   $progress_image  text");
	}
	$tbl_message = $wpdb->prefix . 'Gmgt_message';
	$post_id='post_id';
	if (!in_array($post_id, $wpdb->get_col( "DESC " . $tbl_message, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $tbl_message  ADD   $post_id  int(30)");
	}
	
	$tbl_gmgt_membershiptype = $wpdb->prefix . 'gmgt_membershiptype';
	$on_of_member='on_of_member';
	$classis_limit='classis_limit';
	$on_of_classis='on_of_classis';
	if (!in_array($on_of_member, $wpdb->get_col( "DESC " . $tbl_gmgt_membershiptype, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $tbl_gmgt_membershiptype  ADD   $on_of_member  int(20)");
	}
	if (!in_array($classis_limit, $wpdb->get_col( "DESC " . $tbl_gmgt_membershiptype, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $tbl_gmgt_membershiptype  ADD   $classis_limit  varchar(200)");
	}
	
	if (!in_array($on_of_classis, $wpdb->get_col( "DESC " . $tbl_gmgt_membershiptype, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $tbl_gmgt_membershiptype  ADD   $on_of_classis  int(20)");
	}
	
	$gmgt_reservation = $wpdb->prefix . 'gmgt_reservation';
	$staff_id='staff_id';
	if (!in_array($staff_id, $wpdb->get_col( "DESC " . $gmgt_reservation, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $gmgt_reservation  ADD   $staff_id  int(11)");
	}
	
	$table_gmgt_membership_payment = $wpdb->prefix . 'Gmgt_membership_payment';
	$invoice_no='invoice_no';
	if (!in_array($invoice_no, $wpdb->get_col( "DESC " . $table_gmgt_membership_payment, 0 ) )){  
		$result= $wpdb->query("ALTER     TABLE $table_gmgt_membership_payment  ADD   $invoice_no  varchar(10) NOT NULL");
	}

	  $table_gmgt_store = $wpdb->prefix . 'gmgt_store';
	  $member_id='member_id';
	  $entry='entry';
	  $tax_entry='tax';
	  $discount='discount';
	  $amount='amount';
	  $total_amount='total_amount';
	  $paid_amount='paid_amount';
	  $payment_status='payment_status';
	  $invoice_no='invoice_no';
	  $created_date='created_date';
	  $sell_date='sell_date';
	  $tax_id1='tax_id';	  
	  
		if (!in_array($member_id, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $member_id  int(11) NOT NULL");
		}
		
		if (!in_array($entry, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $entry  text NOT NULL");
		}
		
		if (!in_array($tax_entry, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $tax_entry  double NOT NULL");
		}
		
		if (!in_array($discount, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $discount  double NOT NULL");
		}
		
		if (!in_array($amount, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $amount  double NOT NULL");
		}
		
		if (!in_array($total_amount, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $total_amount  double NOT NULL");
		}
		
		if (!in_array($paid_amount, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $paid_amount  double NOT NULL");
		}
		
		if (!in_array($payment_status, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $payment_status  varchar(20) NOT NULL");
		}
		
		if (!in_array($invoice_no, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $invoice_no  varchar(50) NOT NULL");
		}
		
		if (!in_array($created_date, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $created_date  date NOT NULL");
		}
		
		if (!in_array($sell_date, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_store  ADD   $sell_date  date NOT NULL");
		}
		if (!in_array($tax_id1, $wpdb->get_col( "DESC " . $table_gmgt_store, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER TABLE $table_gmgt_store  ADD  $tax_id1  varchar(100)");
		}
		  $table_gmgt_income_expense = $wpdb->prefix . 'gmgt_income_expense';
		  $invoice_no='invoice_no';
		  $discount='discount';
		  $total_amount='total_amount';
		  $amount='amount';
		  $paid_amount='paid_amount';
		  $tax='tax';
		  $create_by='create_by';	  
		  $tax_id='tax_id';	  
	  
	   if (!in_array($create_by, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_income_expense  ADD   $create_by  int(11) NOT NULL");
		}
	  
	    if (!in_array($invoice_no, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_income_expense  ADD   $invoice_no  varchar(50) NOT NULL");
		}
		
	    if (!in_array($discount, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_income_expense  ADD   $discount  double NOT NULL");
		}
		
		if (!in_array($total_amount, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_income_expense  ADD   $total_amount  double NOT NULL");
		}
		
		if (!in_array($amount, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_income_expense  ADD   $amount  double NOT NULL");
		}
		
		if (!in_array($paid_amount, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_income_expense  ADD   $paid_amount  double NOT NULL");
		}
		
		if (!in_array($tax, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_income_expense  ADD   $tax  double NOT NULL");
		}
		if (!in_array($tax_id, $wpdb->get_col( "DESC " . $table_gmgt_income_expense, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER TABLE $table_gmgt_income_expense  ADD  $tax_id  varchar(100)");
		}
		$table_gmgt_product = $wpdb->prefix . 'gmgt_product';
		$sku_number='sku_number';
		$product_cat_id='product_cat_id';
		$manufacture_company_name='manufacture_company_name';
		$manufacture_date='manufacture_date';
		$product_description='product_description';
		$product_specification='product_specification';
		$product_image='product_image';
		
		if (!in_array($sku_number, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $sku_number varchar(50) NOT NULL");
		}
		
		if (!in_array($product_cat_id, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $product_cat_id  int(11) NOT NULL");
		}
		
		if (!in_array($manufacture_company_name, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $manufacture_company_name  varchar(50) NOT NULL");
		}
		
		if (!in_array($manufacture_date, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $manufacture_date  date");
		}
		
		if (!in_array($product_description, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $product_description  text NOT NULL");
		}
		if (!in_array($product_specification, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $product_specification  text NOT NULL");
		}
		if (!in_array($product_image, $wpdb->get_col( "DESC " . $table_gmgt_product, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_product  ADD   $product_image  varchar(255) NOT NULL");
		}
		
		$table_gmgt_membership_payment = $wpdb->prefix . 'Gmgt_membership_payment';
		$membership_fees_amount='membership_fees_amount';
		$membership_signup_amount='membership_signup_amount';
		$tax_amount='tax_amount';
		
		if (!in_array($membership_fees_amount, $wpdb->get_col( "DESC " . $table_gmgt_membership_payment, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_membership_payment  ADD   $membership_fees_amount  double NOT NULL");
		}
		
		if (!in_array($membership_signup_amount, $wpdb->get_col( "DESC " . $table_gmgt_membership_payment, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER     TABLE $table_gmgt_membership_payment  ADD   $membership_signup_amount  double NOT NULL");
		}
		if (!in_array($tax_amount, $wpdb->get_col( "DESC " . $table_gmgt_membership_payment, 0 ) ))
		{  
		   $result= $wpdb->query("ALTER  TABLE $table_gmgt_membership_payment  ADD   $tax_amount  double NOT NULL");
		}
	$table_gmgt_groups = $wpdb->prefix . 'gmgt_groups';	
	$group_description='group_description';
	if (!in_array($group_description, $wpdb->get_col( "DESC " . $table_gmgt_groups, 0 ) ))
	{  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_groups  ADD  $group_description text NOT NULL");
	}	
	$table_gmgt_membershiptype= $wpdb->prefix . 'gmgt_membershiptype';
	$tax='tax';
	$activity_cat_id='activity_cat_id';
	$activity_cat_status='activity_cat_status';
	if (!in_array($activity_cat_id, $wpdb->get_col( "DESC " . $table_gmgt_membershiptype, 0 ) ))
	{ 	  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_membershiptype  ADD  $activity_cat_id  varchar(100)");
	}
	if (!in_array($activity_cat_status, $wpdb->get_col( "DESC " . $table_gmgt_membershiptype, 0 ) ))
	{ 	  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_membershiptype  ADD  $activity_cat_status  int(11)");
	}
	if (!in_array($tax, $wpdb->get_col( "DESC " . $table_gmgt_membershiptype, 0 ) ))
	{ 	  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_membershiptype  ADD  $tax  varchar(100)");
	}
	
	$table_gmgt_sales_payment_history = $wpdb->prefix . 'gmgt_sales_payment_history';
	$table_gmgt_membership_payment_history = $wpdb->prefix .'gmgt_membership_payment_history';
	$table_gmgt_income_payment_history=$wpdb->prefix.'gmgt_income_payment_history';
	
	$payment_description='payment_description';
	
	if (!in_array($payment_description, $wpdb->get_col( "DESC " . $table_gmgt_sales_payment_history, 0 ) ))
	{ 	  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_sales_payment_history  ADD  $payment_description text");
	}
	if (!in_array($payment_description, $wpdb->get_col( "DESC " . $table_gmgt_membership_payment_history, 0 ) ))
	{ 	  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_membership_payment_history  ADD  $payment_description text");
	}
	if (!in_array($payment_description, $wpdb->get_col( "DESC " . $table_gmgt_income_payment_history, 0 ) ))
	{ 	  
	   $result= $wpdb->query("ALTER  TABLE $table_gmgt_income_payment_history  ADD  $payment_description text");
	}
	
	$start_date='start_date';
	$end_date='end_date';
	$color='color';
	$member_limit='member_limit';
	if (!in_array($start_date, $wpdb->get_col( "DESC " . $table_gmgt_class_schedule, 0 ) ))
	{  
	   $result= $wpdb->query("ALTER     TABLE $table_gmgt_class_schedule  ADD   $start_date  date NOT NULL");
	}
	
	if (!in_array($end_date, $wpdb->get_col( "DESC " . $table_gmgt_class_schedule, 0 ) ))
	{  
	   $result= $wpdb->query("ALTER     TABLE $table_gmgt_class_schedule  ADD   $end_date  date NOT NULL");
	}
	
	if (!in_array($color, $wpdb->get_col( "DESC " . $table_gmgt_class_schedule, 0 ) ))
	{  
	   $result= $wpdb->query("ALTER     TABLE $table_gmgt_class_schedule  ADD   $color  varchar(50) NOT NULL");
	}
	
	if (!in_array($member_limit, $wpdb->get_col( "DESC " . $table_gmgt_class_schedule, 0 ) ))
	{  
	   $result= $wpdb->query("ALTER     TABLE $table_gmgt_class_schedule  ADD   $member_limit  int(11) NOT NULL");
	}
 	$table_gmgt_booking_class = $wpdb->prefix . 'gmgt_booking_class';
	$class_booking_date='class_booking_date';
	if (!in_array($class_booking_date, $wpdb->get_col( "DESC " . $table_gmgt_booking_class, 0 ) ))
	{  
	   $result= $wpdb->query("ALTER TABLE $table_gmgt_booking_class  ADD   $class_booking_date date NOT NULL");
	}
	
	// ADD MEMBERSHIP ADDED BY DEFAULT PLUGIN ACTIVATE TIME //
	$table_membership = $wpdb->prefix. 'gmgt_membershiptype';
	$membership_result = $wpdb->get_results("SELECT * FROM $table_membership where membership_label='Golden Membership'");
	if(empty($membership_result))
	{
		$table_membership = $wpdb->prefix. 'gmgt_membershiptype';
		$member_image_url=get_option( 'gmgt_system_logo' );
		$membershipdata['membership_label']=MJ_gmgt_strip_tags_and_stripslashes('Golden Membership');
		$membershipdata['membership_length_id']='30';		
		$membershipdata['membership_class_limit']='unlimited';
		$membershipdata['classis_limit']='unlimited';	
		$membershipdata['on_of_member']=0;
		$membershipdata['on_of_classis']=0;
		$membershipdata['membership_amount']=0;
		$membershipdata['installment_amount']=0;
		$membershipdata['signup_fee']=0;
		$membershipdata['membership_description']='This is free membership';
		$membershipdata['gmgt_membershipimage']=$member_image_url;
		$membershipdata['created_date']=date("Y-m-d");
		$membershipdata['created_by_id']=get_current_user_id();
		$result=$wpdb->insert( $table_membership, $membershipdata );
	}
   // END CODE MEMBERSHIP ADDED BY DEFAULT PLUGIN ACTIVATE TIME //
   
   //OLD MEMBERSHIP DATA ALL ACTIVITY CATEGORY ADDED //
    global $wpdb;
    $obj_membership=new MJ_Gmgtmembership;
    $membershipdata=$obj_membership->MJ_gmgt_get_all_membership();
	
    $activity_category=MJ_gmgt_get_all_category('activity_category');
	
	$activity_cat_id_array=array();
	if(!empty($activity_category))
	{
		foreach ($activity_category as $retrive_data)
		{
			$activity_cat_id_array[]=$retrive_data->ID;
		}
	}
	else
    {
		$activity_cat_id_array='';
	}

	if(!empty($activity_cat_id_array))
	{
		$activity_category_value=implode(",",$activity_cat_id_array);		
	}
	else
	{
		$activity_category_value=null;		
	}
	
	if(!empty($membershipdata))
    {
		foreach ($membershipdata as $retrieved_data)
		{
			if($retrieved_data->activity_cat_status != 1)
			{
				$membershipid['membership_id']=$retrieved_data->membership_id;
				$membership_data['activity_cat_id']=$activity_category_value;
				$membership_data['activity_cat_status']=1;	
				$result=$wpdb->update( $table_membership, $membership_data ,$membershipid);				
			}			
		}
    }	
   //END OLD MEMBERSHIP DATA ALL ACTIVITY CATEGORY ADDED //
} 
/**
 * Authenticate a user, confirming the username and password are valid.
 *
 * @since 2.8.0
 *
 * @param WP_User|WP_Error|null $user     WP_User or WP_Error object from a previous callback. Default null.
 * @param string                $username Username for authentication.
 * @param string                $password Password for authentication.
 * @return WP_User|WP_Error WP_User on success, WP_Error on failure.
 */
add_filter( 'authenticate', 'wp_authenticate_username_password_new', 20, 3 );

function wp_authenticate_username_password_new( $user, $username, $password )
{
	if ( $user instanceof WP_User ) {
		return $user;
	}

	if ( empty( $username ) || empty( $password ) ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$error = new WP_Error();

		if ( empty( $username ) ) {
			$error->add( 'empty_username', __( '<strong>ERROR</strong>: The username field is empty.' ) );
		}

		if ( empty( $password ) ) {
			$error->add( 'empty_password', __( '<strong>ERROR</strong>: The password field is empty.' ) );
		}

		return $error;
	}

	$user = get_user_by( 'login', $username );

	if ( ! $user ) {
		return new WP_Error(
			'invalid_username',
			__( '<strong>ERROR</strong>: Invalid username.' ) .
			' <a href="' . wp_lostpassword_url() . '">' .
			__( 'Lost your password?' ) .
			'</a>'
		);
	}

	/**
	 * Filters whether the given user can be authenticated with the provided $password.
	 *
	 * @since 2.5.0
	 *
	 * @param WP_User|WP_Error $user     WP_User or WP_Error object if a previous
	 *                                   callback failed authentication.
	 * @param string           $password Password to check against the user.
	 */
	$user = apply_filters( 'wp_authenticate_user', $user, $password );
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		return new WP_Error(
			'incorrect_password',
			sprintf(
				/* translators: %s: user name */
				__( '<strong>ERROR</strong>: No such username or password.' ),
				'<strong>' . $username . '</strong>'
			) .
			' <a href="' . wp_lostpassword_url() . '">' .
			__( 'Lost your password?' ) .
			'</a>'
		);
	}

	return $user;
}

add_filter( 'auth_cookie_expiration', 'keep_me_logged_in_60_minutes' );
function keep_me_logged_in_60_minutes( $expirein ) {
    return 3600; // 1 hours
}

//Auto Fill Feature is Enabled  wp login page//
add_action('login_form', function($args) {
  $login = ob_get_contents();
  ob_clean();
  $login = str_replace('id="user_pass"', 'id="user_pass" autocomplete="off"', $login);
  $login = str_replace('id="user_login"', 'id="user_login" autocomplete="off"', $login);
  echo $login; 
}, 9999);

// Wordpress User Information Dislclosure//
remove_action( 'rest_api_init', 'create_initial_rest_routes', 99 );

 ////X-Frame-Options Header Not Set﻿//
function block_frames() {
header( 'X-FRAME-OPTIONS: SAMEORIGIN' );
}
add_action( 'send_headers', 'block_frames', 10 );
add_action( 'send_headers', 'send_frame_options_header', 10, 0 );

if (!empty($_SERVER['HTTPS'])) {
  function add_hsts_header($headers) {
    $headers['strict-transport-security'] = 'max-age=31536000; includeSubDomains';
    return $headers;
  }
add_filter('wp_headers', 'add_hsts_header');
}
?>