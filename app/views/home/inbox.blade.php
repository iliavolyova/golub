<div class="container">
    {{ Notification::showAll() }}

    <div class="page-header">
        @if($viewing === 'inbox')
            <h2>@lang('home.inbox.inbox')</h2>
        @elseif($viewing === 'outbox')
            <h2>@lang('home.inbox.outbox')</h2>
        @elseif($viewing === 'favorites')
            <h2>@lang('home.inbox.favorites')</h2>
        @endif
    </div>

    <div class="row col-md-12">
        <div class="panel-group" id="accordion">
            @foreach($mailovi as $mail)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">

                            <span class="row">
                                <span class="col-md-1">
                                    <a class="btn-link" onclick=fav({{$mail->id}})>
                                        <input type="checkbox" class="glyphicon glyphicon-star-empty" {{ $mail->fav ? "checked" : ""  }}>
                                    </a>
                                </span>
                                <a data-toggle="collapse" data-parent="#accordion" href="{{'#msg' . $mail->id}}">
                                    <span id="{{'mailsender' . $mail->id}}"
                                          data-addr="{{$viewing !== 'outbox' ? $mail->sender : $mail->receiver}}"
                                          class="col-md-3">{{$viewing !== 'outbox' ? $mail->sender_fullname : $mail->receiver_fullname }}</span>
                                    <span id="{{'mailsubject' . $mail->id}}" class="col-md-8">{{ $mail->subject }}</span>
                                </a>
                            </span>
                        </h4>
                    </div>
                    <div id="{{'msg' . $mail->id}}" class="panel-collapse collapse">
                        <div class="panel-body">
                            <span id="{{'mailcontent' . $mail->id}}">
                                {{ $mail->content }}
                            </span>

                            <br><hr/>
                            <span class="btn-group">
                                @if($viewing != 'outbox')
                                <button class="btn btn-info" onclick=reply({{$mail->id}})>@lang('home.inbox.reply')</button>
                                @endif
                                <button class="btn btn-info" onclick=forward({{$mail->id}})>@lang('home.inbox.forward')</button>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>