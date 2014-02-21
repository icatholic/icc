Ext.define('icc.view.idatabase.Plugin.System.Edit', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabasePluginSystemEdit',
	title : '添加系统插件',
	initComponent : function() {
		this.items = [ {
			xtype : 'iform',
			url : '/idatabase/plugin/edit-plugin',
			items : [ {
				xtype : 'hiddenfield',
				name : '_id',
				fieldLabel : '插件编号',
				allowBlank : false
			}, {
				name : 'name',
				fieldLabel : '插件名称',
				allowBlank : false
			}, {
				xtype : 'textareafield',
				name : 'desc',
				fieldLabel : '插件描述',
				allowBlank : false
			}, {
				name : 'xtype',
				fieldLabel : '插件xtype',
				allowBlank : false
			} ]
		} ];

		this.callParent();
	}

});