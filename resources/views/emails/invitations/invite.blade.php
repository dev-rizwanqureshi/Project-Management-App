<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You're invited to Riraa</title>
</head>
<body style="margin: 0; background: #f5f3ff; color: #211a32; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <div style="padding: 40px 16px;">
        <div style="max-width: 560px; margin: 0 auto; overflow: hidden; border: 1px solid #e9e4f4; border-radius: 24px; background: #ffffff; box-shadow: 0 18px 50px rgba(76, 29, 149, 0.12);">
            <div style="padding: 28px 32px; background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 48%, #0ea5e9 100%); color: #ffffff;">
                <div style="font-size: 22px; font-weight: 800; letter-spacing: -0.03em;">Riraa</div>
                <div style="margin-top: 8px; color: rgba(255,255,255,0.82); font-size: 13px;">Clear work. Calm execution.</div>
            </div>

            <div style="padding: 36px 32px;">
                <p style="margin: 0; color: #7c3aed; font-size: 12px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase;">You're invited</p>
                <h1 style="margin: 12px 0 0; color: #211a32; font-size: 30px; line-height: 1.15; letter-spacing: -0.04em;">Join {{ $invitation->company->name }} on Riraa</h1>
                <p style="margin: 20px 0 0; color: #5b536c; font-size: 16px; line-height: 1.7;">
                    {{ $invitation->inviter->name }} invited you to collaborate
                    @if ($invitation->board)
                        on the <strong style="color: #211a32;">{{ $invitation->board->name }}</strong> board
                    @elseif ($invitation->workspace)
                        in the <strong style="color: #211a32;">{{ $invitation->workspace->name }}</strong> workspace
                    @else
                        with the company
                    @endif
                    as a <strong style="color: #211a32;">{{ $invitation->role === 'guest' ? 'Viewer' : ucfirst($invitation->role) }}</strong>.
                </p>

                <div style="margin: 28px 0; padding: 18px 20px; border: 1px solid #ede9fe; border-radius: 16px; background: #faf9ff;">
                    <div style="color: #7c3aed; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;">Invitation details</div>
                    <div style="margin-top: 10px; color: #211a32; font-size: 15px; line-height: 1.8;">
                        <div><strong>Company:</strong> {{ $invitation->company->name }}</div>
                        @if ($invitation->workspace)
                            <div><strong>Workspace:</strong> {{ $invitation->workspace->name }}</div>
                        @endif
                        @if ($invitation->board)
                            <div><strong>Board:</strong> {{ $invitation->board->name }}</div>
                        @endif
                        <div><strong>Access:</strong> {{ $invitation->role === 'guest' ? 'Viewer' : ucfirst($invitation->role) }}</div>
                    </div>
                </div>

                <a href="{{ $invitationUrl }}" style="display: inline-block; padding: 14px 22px; border-radius: 12px; background: linear-gradient(135deg, #7c3aed, #2563eb); color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none;">Accept invitation&nbsp; →</a>

                <p style="margin: 28px 0 0; color: #8a8299; font-size: 13px; line-height: 1.6;">
                    This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}. If you were not expecting it, you can safely ignore this email.
                </p>
            </div>
        </div>
        <p style="max-width: 560px; margin: 18px auto 0; color: #958da5; font-size: 12px; text-align: center;">Sent by Riraa · Clear work. Calm execution.</p>
    </div>
</body>
</html>
