Ext.define('icc.view.idatabase.Statistic.Combobox.Period', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStatisticComboboxPeriod',
	fieldLabel : '统计周期',
	name : 'period',
	store : 'idatabase.Statistic.Period',
	valueField : 'value',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false
});
