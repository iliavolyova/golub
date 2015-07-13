

<div class="container">
    <div class="page-header">
        <h2>Inbox</h2>
    </div>

    <div class="row col-md-12">
        <div class="panel-group" id="accordion">
            @foreach($mailovi as $mail)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#accordion" href="{{"#" . $mail->id}}">
                                <span class="row">
                                    <span class="col-md-4">{{ $mail->sender }}</span>
                                    <span class="col-md-8">{{ $mail->subject }}</span>
                                </span>
                            </a>
                        </h4>
                    </div>
                    <div id="{{$mail->id}}" class="panel-collapse collapse">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="well">{{ $mail->content }}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="btn btn-info">Reply</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>