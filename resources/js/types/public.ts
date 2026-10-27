export type PublicActorSummary = {
    id: number;
    name: string;
    activity_name: string | null;
};

export type PublicVideoSummary = {
    id: number;
    title: string;
    published_at: string | null;
    youtube_url: string;
    channel: {
        id: number | null;
        title: string;
    } | null;
};

export type PublicChannelSummary = {
    id: number;
    title: string;
    handle: string | null;
    video_count: number;
};

export type PublicChannelDetail = PublicChannelSummary & {
    videos: PublicVideoSummary[];
};

export type PublicVideoDetail = PublicVideoSummary & {
    actors: PublicActorSummary[];
};

export type PublicActorDetail = PublicActorSummary & {
    birth_date: string | null;
    birth_place: string | null;
    education: string | null;
    debut: string | null;
    agency: string | null;
    nationality: string | null;
    reference_urls: string[];
    videos: PublicVideoSummary[];
};
