Ext.define('icc.view.idatabase.Index.Grid',{
	extend : 'Ext.grid.Panel',
	alias : 'widget.idatabaseIndexGrid',
	requires : [ 'icc.common.Paging','icc.view.common.Combobox.Boolean' ],
	collapsible : false,
	closable : false,
	multiSelect : true,
	disableSelection : false,
	sortableColumns : false,
	initComponent : function() {
		var me = this;
		var store = Ext.create('icc.store.idatabase.Index');
		store['proxy']['extraParams']['__PROJECT_ID__'] = me.__PROJECT_ID__;
		store['proxy']['extraParams']['__COLLECTION_ID__'] = me.__COLLECTION_ID__;
		store.load();

		Ext.apply(me,{
			store : store,
			bbar : {
				xtype : 'paging',
				store : store
			},
			selType : 'rowmodel',
			plugins : [ Ext.create('Ext.grid.plugin.CellEditing',{
				clicksToEdit : 2
			})],
			dockedItems : [ {
				xtype : 'toolbar',
				dock : 'top',
				items : [ {
					text : '操作',
					iconCls : 'menu',
					width : 100,
					menu : {
						xtype : 'menu',
						plain : true,
						items : [ {
							xtype : 'button',
							text : '新增',
							iconCls : 'add',
							action : 'add'
						}, {
							xtype : 'button',
							text : '删除',
							iconCls : 'remove',
							action : 'remove'
						} ]
					}
				} ]
			}],
			columns : [{
				text : '_id',
				dataIndex : '_id',
				hidden : true
			},{
				text : '索引条件',
				dataIndex : 'keys',
				flex : 1,
				field : {
					xtype : 'textfield'
				}
			},{
				xtype : 'datecolumn',
				text : '创建时间',
				dataIndex : '__CREATE_TIME__',
				flex : 1,
				format : 'Y-m-d',
				hidden : false
			}]
		});
	
		me.callParent();
	}
});