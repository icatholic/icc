Ext.define('icc.view.idatabase.Collection.Grid', {
	extend : 'Ext.grid.Panel',
	alias : 'widget.idatabaseCollectionGrid',
	requires : [ 'icc.common.Paging' ],
	title : '数据管理',
	collapsible : false,
	closable : false,
	multiSelect : false,
	disableSelection : false,
	sortableColumns : false,
	columns : [ {
		text : '集合ID',
		dataIndex : '_id',
		flex : 1,
		hidden : true
	}, {
		text : '集合名称',
		dataIndex : 'name',
		flex : 2
	}, {
		text : '集合别名',
		dataIndex : 'alias',
		flex : 1,
		hidden : true
	}, {
		text : '排序',
		dataIndex : 'orderBy',
		flex : 1,
		hidden : true
	}, {
		xtype : 'datecolumn',
		text : '创建时间',
		dataIndex : '__CREATE_TIME__',
		flex : 1,
		format : 'Y-m-d',
		hidden : true
	} ],
	initComponent : function() {
		var me = this;

		var store = Ext.create('icc.store.idatabase.Collection');
		store.proxy.extraParams = {
			'__PROJECT_ID__' : me.__PROJECT_ID__,
			'__PLUGIN_ID__' : me.__PLUGIN_ID__
		};
		store.load();

		Ext.apply(me, {
			__PROJECT_ID__ : me.__PROJECT_ID__,
			store : store,
			bbar : {
				xtype : 'paging',
				store : store
			},
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
				}, '-', {
					text : '管理',
					width : 100,
					iconCls : 'menu',
					menu : {
						xtype : 'menu',
						plain : true,
						items : [ {
							xtype : 'button',
							text : '属性管理',
							iconCls : 'structure',
							action : 'structure'
						}, {
							xtype : 'button',
							text : '排序管理',
							iconCls : 'orderBy',
							action : 'orderBy'
						}, {
							xtype : 'button',
							text : '索引管理',
							iconCls : 'index',
							action : 'index'
						}, {
							xtype : 'button',
							text : '统计管理',
							iconCls : 'static',
							action : 'statistic'
						}, {
							xtype : 'button',
							text : '映射管理',
							iconCls : 'mapping',
							action : 'mapping'
						}, {
							xtype : 'button',
							text : '安全管理',
							iconCls : 'lock',
							action : 'lock'
						}, {
							xtype : 'button',
							text : '数据导入',
							iconCls : 'dbimport',
							action : 'dbimport'
						} , {
							xtype : 'button',
							text : '同步插件',
							iconCls : 'sync',
							action : 'sync'
						}  , {
							xtype : 'button',
							text : '触发动作',
							iconCls : 'sync',
							action : 'hook'
						} ]
					}
				}, '-', {
					xtype : 'searchBar',
					store : store
				} ]
			} ]
		});

		me.callParent();
	}

});