@extends('layouts.base')

@section('body')
<div class="container">
    {{ Notification::showAll() }}

    <div class="jumbotron vertical-center">
        <div class="container text-center">
            <h1>@lang('home.header')</h1>
            <div class="row">
                <img src="logo.jpg">
            </div>
            <br>
            <div class="row">
                <a href="{{$googleauth}}" class="btn btn-primary">Login with google and list my emails!</a>
            </div>
        </div>
    </div>
</div>
@stop