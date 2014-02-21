Ext.define('icc.view.idatabase.Statistic.Combobox.Axis', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStatisticComboboxAxis',
	fieldLabel : '统计类型',
	name : 'type',
	store : 'idatabase.Statistic.Axis',
	valueField : 'value',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false
});
