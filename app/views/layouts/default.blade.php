@extends('layouts.base')

@section('body')

    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container">
            <div class="navbar-header"><a class="navbar-brand" href="#">@lang('home.navbar.brand')</a></div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="/inbox">@lang('home.navbar.inbox')</a></li>
                    <li><a href="/outbox">@lang('home.navbar.outbox')</a></li>
                    <li><a href="/favorites">@lang('home.navbar.favorites')</a></li>
                    <li><p class="navbar-btn"><a class="btn btn-info" onclick=compose()>@lang('home.navbar.compose')</a></p></li>
                </ul>

                <ul class="nav navbar-nav navbar-right">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" > @lang('home.navbar.language') <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="/lang/en">English</a></li>
                            <li><a href="/lang/hr">Hrvatski</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" > {{$username}} <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="/logout">@lang('home.navbar.logout')</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <br/><br/><br/>


    {{ $content }}

    <!-- Compose message modal -->
    <div class="modal fade" id="modal_reply" tabindex="-1" role="dialog" aria-hidden="true" >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="modal-title" id="modal-title">Reply to email</h3>
                </div>

                <div class="modal-body">
                    {{ Form::open(array('route' => 'sendmail', 'method' => 'POST', 'class' => 'clearfix', 'id' => 'sendmailform')) }}
                    <div class="form-group">
                        <label for="tofield">@lang('home.modal.recipient')</label>
                        {{ Form::text('To', '', array('class' => 'form-control required email', 'id' => 'tofield', 'data-placement' => 'top', 'data-trigger' => 'manual', 'data-content' => 'Must be valid email')) }}
                    </div>
                    <div class="form-group">
                        <label for="subjectfield">@lang('home.modal.subject')</label>
                        {{ Form::text('Subject', '', array('class' => 'form-control required', 'id' => 'subjectfield', 'data-placement' => 'top', 'data-trigger' => 'manual', 'data-content' => 'This field is required'))}}
                    </div>
                    <div class="form-group">
                        <label for="messagefield">@lang('home.modal.message')</label>
                        {{ Form::textarea('Message', '', array('class' => 'form-control required', 'id' => 'messagefield')) }}
                    </div>
                    {{ Form::close() }}
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('home.modal.cancel')</button>
                    <button type="submit" class="btn btn-success" form="sendmailform">@lang('home.modal.send')</button>
                </div>
            </div><!-- modal content -->
        </div><!-- modal-dialog -->
    </div>

@stop