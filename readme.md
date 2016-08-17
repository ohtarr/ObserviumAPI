#Quick and Simple API to perform the following actions with Observium:
GET Parameters:
	type=device : Retrieve all devices
		id=## : Retrieve a specific device by id
		name=xyz : Retrieve a specific device by name

	type=group: Retrieve all groups


POST parameters:
	"action":
		add_device:  Add a device by NAME or ID.
			name
		delete_device: Remove a device by NAME or ID.
			name
		modify_device: modify parameters on a single device.  Currently used to disable specific poller and discovery modules.
			disable_port_polling
			disable_port_discovery
		add_group: Add a group
			group_type
			name
			description
			device_association
			entity_association
		delete_group
			name

