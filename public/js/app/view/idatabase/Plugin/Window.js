Ext.define('icc.view.idatabase.Plugin.Window', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabasePluginWindow',
	title : '项目插件管理',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'idatabasePluginGrid',
				__PROJECT_ID__ : this.__PROJECT_ID__
			} ]
		});

		this.callParent();
	}

});