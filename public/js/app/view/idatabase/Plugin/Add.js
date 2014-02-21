Ext.define('icc.view.idatabase.Plugin.Add', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabasePluginAdd',
	title : '添加插件',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/plugin/add',
				items : [ {
					xtype : 'hiddenfield',
					name : '__PROJECT_ID__',
					fieldLabel : '插件编号',
					allowBlank : false,
					value : this.__PROJECT_ID__
				}, {
					xtype : 'idatabasePluginCombobox',
					allowBlank : false
				}, {
					xtype : 'idatabaseProjectCombobox',
					fieldLabel : '共享来源项目',
					name : '__SOURCE_PROJECT_ID__',
					allowBlank : true
				} ]
			} ]
		});

		this.callParent();
	}

});