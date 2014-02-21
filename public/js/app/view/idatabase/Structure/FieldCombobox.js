Ext.define('icc.view.idatabase.Structure.FieldCombobox', {
	extend: 'icc.common.Combobox',
	alias: 'widget.idatabaseStructureFieldCombobox',
	fieldLabel: '字段列表',
	name: 'structure',
	store: 'idatabase.Structure',
	valueField: 'field',
	displayField: 'label',
	queryMode: 'remote',
	editable: false,
	typeAhead: false,
	initComponent: function() {
		var store = Ext.create('icc.store.idatabase.Structure');
		store.proxy.extraParams['__PROJECT_ID__'] = this.__PROJECT_ID__;
		store.proxy.extraParams['__COLLECTION_ID__'] = this.__COLLECTION_ID__;
		store.load();
		store.addListener('load', function() {
			var rec = store.findRecord('field', '__CREATE_TIME__', 0, false, false, true);
			if (rec == null) {
				var insertRecord = {};
				insertRecord['label'] = '创建时间';
				insertRecord['field'] = '__CREATE_TIME__';
				store.insert(0, Ext.create(store.model, insertRecord));
			}
			return true;
		});

		Ext.apply(this, {
			store: store
		});

		this.callParent();
	}
});