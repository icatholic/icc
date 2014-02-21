Ext.define('icc.view.idatabase.Plugin.Combobox', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabasePluginCombobox',
	fieldLabel : '系统插件',
	name : '__PLUGIN_ID__',
	store : 'idatabase.Plugin.System',
	valueField : '_id',
	displayField : 'name',
	queryMode : 'remote',
	pageSize : 0,
	editable : false,
	typeAhead : false
});
