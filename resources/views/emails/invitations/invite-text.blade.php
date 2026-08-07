You're invited to join {{ $invitation->company->name }} on Riraa

{{ $invitation->inviter->name }} invited you as {{ $invitation->role === 'guest' ? 'Viewer' : ucfirst($invitation->role) }}.
@if ($invitation->board)
Board: {{ $invitation->board->name }}
@elseif ($invitation->workspace)
Workspace: {{ $invitation->workspace->name }}
@else
Company invitation
@endif

Accept your invitation:
{{ $invitationUrl }}

This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}.
