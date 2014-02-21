Ext.define('icc.view.idatabase.Plugin.Edit', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabasePluginEdit',
	title : '编辑插件',
	initComponent : function() {
		this.items = [ {
			xtype : 'iform',
			url : '/idatabase/plugin/edit',
			items : [ {
				xtype : 'hiddenfield',
				name : '_id',
				fieldLabel : '插件编号',
				allowBlank : false
			},{
				xtype : 'hiddenfield',
				name : '__PROJECT_ID__',
				fieldLabel : '项目编号',
				allowBlank : false,
				value : this.__PROJECT_ID__
			}, {
				xtype : 'idatabasePluginCombobox'
			}]
		} ];

		this.callParent();
	}

});