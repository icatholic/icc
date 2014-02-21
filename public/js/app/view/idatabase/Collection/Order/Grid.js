Ext.define('icc.view.idatabase.Collection.Order.Grid', {
	extend : 'Ext.grid.Panel',
	alias : 'widget.idatabaseCollectionOrderGrid',
	requires : [ 'icc.common.Paging' ],
	collapsible : false,
	closable : false,
	multiSelect : false,
	disableSelection : false,
	sortableColumns : false,
	selType : 'rowmodel',
	plugins : [ Ext.create('Ext.grid.plugin.CellEditing', {
		clicksToEdit : 2
	}) ],
	initComponent : function() {
		var me = this;

		var store = Ext.create('icc.store.idatabase.Collection.Order');
		store.proxy.extraParams = {
			'__PROJECT_ID__' : me.__PROJECT_ID__,
			'__COLLECTION_ID__' : me.__COLLECTION_ID__
		};
		store.load();
		
		var structureStore = Ext.create('icc.store.idatabase.Structure');
		structureStore['proxy']['extraParams']['__PROJECT_ID__'] =  me.__PROJECT_ID__;
		structureStore['proxy']['extraParams']['__COLLECTION_ID__'] =  me.__COLLECTION_ID__;
		me.on({
			beforerender : function() {
				structureStore.load(function(store, records, success) {
					if (success) {
						me.getView().refresh();
					}
				});
			}
		});
		
		var columns = [ {
			text : '字段名称',
			dataIndex : 'field',
			flex : 1,
			field : {
				xtype : 'combobox',
				store : structureStore,
				displayField : 'label',
				valueField : 'field',
				queryMode : 'remote',
				pageSize : 50,
				editable : false,
				typeAhead : false,
				allowBlank : false
			},
			renderer : function(value) {
				var record = structureStore.findRecord('field',value);
				if (record != null) {
					return record.get('label');
				}
				return value;
			}
		}, {
			text : '排序',
			dataIndex : 'order',
			flex : 1,
			field : {
				xtype : 'numberfield',
				minValue : -1,
				maxValue : 1,
				allowBlank : false
			}
		}, {
			text : '优先级',
			dataIndex : 'priority',
			flex : 1,
			field : {
				xtype : 'numberfield',
				allowBlank : false
			}
		}, {
			xtype : 'datecolumn',
			text : '创建时间',
			dataIndex : '__CREATE_TIME__',
			flex : 1,
			format : 'Y-m-d'
		}, {
			xtype : 'datecolumn',
			text : '创建时间',
			dataIndex : '__MODIFY_TIME__',
			flex : 1,
			format : 'Y-m-d',
			hidden : true
		} ];

		Ext.apply(me, {
			__PROJECT_ID__ : me.__PROJECT_ID__,
			__COLLECTION_ID__ : me.__COLLECTION_ID__,
			store : store,
			bbar : {
				xtype : 'paging',
				store : store
			},
			columns : columns,
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
							text : '编辑',
							iconCls : 'edit',
							action : 'edit'
						}, {
							xtype : 'button',
							text : '保存',
							iconCls : 'save',
							action : 'save'
						}, {
							xtype : 'button',
							text : '删除',
							iconCls : 'remove',
							action : 'remove'
						} ]
					}
				} ]
			} ]
		});

		me.callParent();
	}

});