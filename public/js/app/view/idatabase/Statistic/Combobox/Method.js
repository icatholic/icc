Ext.define('icc.view.idatabase.Statistic.Combobox.Method', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStatisticComboboxMethod',
	fieldLabel : '统计方法',
	name : 'method',
	store : 'idatabase.Statistic.Method',
	valueField : 'value',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false
});
