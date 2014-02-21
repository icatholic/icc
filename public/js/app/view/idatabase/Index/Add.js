Ext.define('icc.view.idatabase.Index.Add', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseIndexAdd',
	title : '添加索引',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/index/add',
				fieldDefaults : {
					labelAlign : 'left',
					labelWidth : 150,
					anchor : '100%'
				},
				items : [ {
					xtype : 'hiddenfield',
					name : '__PROJECT_ID__',
					fieldLabel : '项目编号',
					allowBlank : false,
					value : this.__PROJECT_ID__
				}, {
					xtype : 'hiddenfield',
					name : '__COLLECTION_ID__',
					fieldLabel : '集合编号',
					allowBlank : false,
					value : this.__COLLECTION_ID__
				}, {
					xtype : 'textareafield',
					name : 'keys',
					fieldLabel : '索引条件',
					allowBlank : false
				}]
			} ]
		});

		this.callParent();
	}

});