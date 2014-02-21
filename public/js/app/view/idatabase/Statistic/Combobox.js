Ext.define('icc.view.idatabase.Statistic.Combobox', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStatisticCombobox',
	fieldLabel : '统计方法列表',
	name : 'structure',
	store : 'idatabase.Statistic',
	valueField : '_id',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false,
	initComponent : function() {
		var store = Ext.create('icc.store.idatabase.Statistic');
		store.proxy.extraParams = {
			'__PROJECT_ID__' : this.__PROJECT_ID__,
			'__COLLECTION_ID__' : this.__COLLECTION_ID__
		}
		store.load();

		Ext.apply(this, {
			store : store
		});

		this.callParent();
	}
});
