Ext.define('icc.view.idatabase.Collection.Password', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseCollectionPassword',
	title : '安全访问密码',
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
			fieldLabel : '安全密码',
			allowBlank : false
		} ];

		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/lock/verify',
				items : items
			} ]
		});

		this.callParent();
	}
});