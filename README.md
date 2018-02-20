# Alcatel-Lucent Enteprise - OmniPCX Open Gateway (02G) - PLAYGROUND 

*For developers onboarding and playground purpose.*

This is a simple **API proxy** running on **PHP** server side. 
This proxy take POST request from a Client and call the O2G REST API with propper ressources and return the response to the initial Request. For some ressources, this acts as a wrapper. 

[ALE OpenGateway Home Page](http://opengateway.ale-aapp.com/)
[ALE Application Partner Program (developer program)](https://www.al-enterprise.com/en/partners/aapp/)

## METHOD : POST
## URI : {YOUR PHP SERVER}/o2g_api_proxy.php
## PARAMETERS :

	method  :
	 
		create_session | refresh_session | get_session_info | logout_session
		get_user_status
		get_status
		get_pbxs_list
		get_users_list | get_user_details 
		get_user_routing | forward_to | cancel_forward
		get_subscriber_details | put_subscriber
		get_model
		make_call | get_call | basiccall_answer | basiccall_drop
		get_comlog | delete_comlog | ack_comlog
		get_mailboxes | 
		subscribe | 

	token : 
	loginName : 
	companyPhone :
	nodeId : 
	recordId : 
	callee : 


## settings 
1. define your O2G URI in `define("ROXE_FQDN", 'URL:PORT/api/rest')`
2. define your HTP poxy (if any)  in `define("HTTP_PROXY", '')`


