Ext.define('icc.view.idatabase.Collection.AllCombobox', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseCollectionAllCombobox',
	fieldLabel : '集合列表',
	store : 'idatabase.Collection',
	valueField : 'alias',
	displayField : 'name',
	queryMode : 'remote',
	pageSize : 20,
	editable : false,
	typeAhead : false,
	initComponent : function() {
		var store = Ext.create('icc.store.idatabase.Collection.All');
		store.proxy.extraParams['__PROJECT_ID__'] = this.__PROJECT_ID__;
		store.load();
		
		Ext.apply(this,{
			store : store
		});
		
		this.callParent();
	}
});
