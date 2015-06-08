Ext.define('icc.model.idatabase.Collection', {
	extend : 'icc.model.common.Model',
	fields : [ {
		name : 'name',
		type : 'string'
	}, {
		name : 'alias',
		type : 'string'
	}, {
		name : 'type',
		type : 'string'
	}, {
		name : 'isProfessional',
		type : 'boolean'
	}, {
		name : 'isTree',
		type : 'boolean'
	}, {
		name : 'desc',
		type : 'string'
	}, {
		name : 'orderBy',
		type : 'int'
	}, {
		name : 'plugin',
		type : 'boolean'
	}, {
		name : 'plugin_id',
		type : 'string'
	}, {
		name : 'plugin_collection_id',
		type : 'string'
	}, {
		name : 'isRowExpander',
		type : 'boolean'
	}, {
		name : 'rowExpanderTpl',
		type : 'string'
	}, {
		name : 'locked',
		type : 'boolean'
	}, {
		name : 'isAutoHook',
		type : 'boolean'
	}, {
		name : 'defaultSourceData',
		type : 'boolean'
	}, {
		name : 'hook',
		type : 'string'
	}, {
		name : 'hookKey',
		type : 'string'
	}, {
		name : 'hook_notify_email',
		type : 'string'
	}, {
		name : 'hook_debug_mode',
		type : 'boolean'
	}, {
		name : 'isAllowHttpAccess',
		type : 'boolean'
	}, {
		name : 'promissionDefinition',
		type : 'array'
	}, {
		name : 'submitConfirm',
		type : 'boolean'
	}, {
		name : 'submitConfirmInfo',
		type : 'string'
	}]
});