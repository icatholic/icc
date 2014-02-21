Ext.define('icc.view.idatabase.Data.Password', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseDataPassword',
	title : '身份确认',
	initComponent : function() {
		var items = [ {
			xtype : 'hiddenfield',
			name : '__PROJECT_ID__',
			value : this.__PROJECT_ID__,
			allowBlank : false
		}, {
			xtype : 'hiddenfield',
			name : '__COLLECTION_ID__',
			value : this.__COLLECTION_ID__,
			allowBlank : false
		}, {
			name : 'password',
			inputType : 'password',
			fieldLabel : '登录密码',
			allowBlank : false
		} ];

		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/data/drop',
				items : items
			} ]
		});

		this.callParent();
	}
});