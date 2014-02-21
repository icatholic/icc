Ext.define('icc.view.idatabase.Plugin.System.Add', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabasePluginSystemAdd',
	title : '添加系统插件',
	initComponent: function() {
		this.items = [ {
			xtype : 'iform',
			url : '/idatabase/plugin/add-plugin',
			items : [{
				name : 'name',
				fieldLabel : '插件名称',
				allowBlank : false
			},{
				xtype: 'textareafield',
				name : 'desc',
				fieldLabel : '插件描述',
				allowBlank : false
			}, {
				name : 'xtype',
				fieldLabel : '插件xtype',
				allowBlank : false,
				value : 'idatabaseDataGrid'
			}]
		}];
		
        this.callParent(arguments);
    }
	
});