<?php


class Email extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */

    public $timestamps = false;

    protected $table = 'emails';

    protected $fillable = array("google_id", 'account', 'sender','receiver','subject','content', 'tstamp');

}
