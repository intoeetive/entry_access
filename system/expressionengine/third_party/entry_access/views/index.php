<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=entry_access');?>

<?php 
$this->table->set_template($cp_pad_table_template);

$this->table->set_heading(
    array('data' => '', 'style' => 'width:50%;'),
    array('data' => '', 'style' => 'width:50%;')
);

foreach ($settings as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();

$this->table->clear();
?>

<?=$out?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>