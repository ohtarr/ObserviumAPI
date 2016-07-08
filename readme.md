#Quick and Simple API to perform the following actions with Observium:
GET Parameters:
	NONE: Retrieve all devices
	id: Retrieve a specific device by id
	name: Retrieve a specific device by name

POST parameters:
	"action":
		add_device:  Add a device by NAME or ID.
			requires parameter "name" along with it.
		delete_device: Remove a device by NAME or ID.
			requires parameter "name" along with it.


	"hostname": FQDN HOSTNAME of device you wish to perform ACTION on.

	"debug": set to "1" to receive additional debug information in the response body.			
